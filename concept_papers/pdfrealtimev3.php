<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Editor with Image Resizing, Dragging, and Export</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js";
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        #pdf-container {
            position: relative;
            margin: 20px auto;
            border: 1px solid #ccc;
            overflow: hidden;
        }
        canvas {
            display: block;
        }
        .image-wrapper {
            position: absolute;
            cursor: move;
            transform-origin: center;
            border: 2px dashed #007BFF;
        }
        img {
            display: block;
            width: 100%;
            height: 100%;
        }
        .resize-handle {
            width: 12px;
            height: 12px;
            background: #007BFF;
            position: absolute;
            border-radius: 50%;
        }
        .resize-handle.top-left { top: -6px; left: -6px; cursor: nw-resize; }
        .resize-handle.top-right { top: -6px; right: -6px; cursor: ne-resize; }
        .resize-handle.bottom-left { bottom: -6px; left: -6px; cursor: sw-resize; }
        .resize-handle.bottom-right { bottom: -6px; right: -6px; cursor: se-resize; }
        button {
            margin: 10px;
            padding: 10px 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <h2>PDF Editor with Image Resizing, Dragging, and Export</h2>

    <input type="file" id="pdf-upload" accept="application/pdf"><br><br>
    <input type="file" id="image-upload" accept="image/*"><br><br>

    <button onclick="downloadModifiedPDF()">Download Edited PDF</button>

    <div id="pdf-container"></div>

    <script>
        let pdfDoc = null;
        let pdfBytes = null;
        let canvas, ctx;

        let imgElement = null;
        let wrapper = null;

        let imgX = 50, imgY = 50;
        let imgWidth = 150, imgHeight = 150;

        let pdfWidth = 0, pdfHeight = 0;

        let isResizing = false;
        let isDragging = false;

        let startX, startY, startWidth, startHeight, offsetX, offsetY;

        document.getElementById('pdf-upload').addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.readAsArrayBuffer(file);
            reader.onload = async () => {
                pdfBytes = reader.result;
                pdfDoc = await PDFLib.PDFDocument.load(pdfBytes);

                const firstPage = pdfDoc.getPages()[0];
                const { width, height } = firstPage.getSize();

                pdfWidth = width;
                pdfHeight = height;

                renderPDFWithPreview(pdfBytes);
            };
        });

        async function renderPDFWithPreview(pdfBytes) {
            const pdfContainer = document.getElementById("pdf-container");
            pdfContainer.innerHTML = "";

            const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
            const pdf = await loadingTask.promise;
            const page = await pdf.getPage(1);

            const viewport = page.getViewport({ scale: 1 });

            canvas = document.createElement("canvas");
            canvas.width = pdfWidth;
            canvas.height = pdfHeight;

            pdfContainer.style.width = `${pdfWidth}px`;
            pdfContainer.style.height = `${pdfHeight}px`;

            pdfContainer.appendChild(canvas);

            ctx = canvas.getContext("2d");

            const renderContext = {
                canvasContext: ctx,
                viewport: page.getViewport({ scale: pdfWidth / viewport.width })
            };

            await page.render(renderContext);

            if (wrapper) {
                pdfContainer.appendChild(wrapper);
            }
        }

        document.getElementById('image-upload').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (wrapper) {
                wrapper.remove();
            }

            const reader = new FileReader();
            reader.readAsDataURL(file);

            reader.onload = () => {
                const pdfContainer = document.getElementById("pdf-container");

                wrapper = document.createElement("div");
                wrapper.classList.add("image-wrapper");
                wrapper.style.left = `${imgX}px`;
                wrapper.style.top = `${imgY}px`;
                wrapper.style.width = `${imgWidth}px`;
                wrapper.style.height = `${imgHeight}px`;

                imgElement = new Image();
                imgElement.src = reader.result;
                imgElement.style.width = "100%";
                imgElement.style.height = "100%";

                wrapper.appendChild(imgElement);

                addResizeHandles(wrapper);

                pdfContainer.appendChild(wrapper);

                addDragAndResize(wrapper);
            };
        });

        function addResizeHandles(wrapper) {
            const handles = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];

            handles.forEach(handle => {
                const div = document.createElement('div');
                div.classList.add('resize-handle', handle);
                wrapper.appendChild(div);
            });
        }

        function addDragAndResize(wrapper) {
            wrapper.onmousedown = (e) => {
                if (e.target.classList.contains('resize-handle')) {
                    isResizing = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startWidth = wrapper.offsetWidth;
                    startHeight = wrapper.offsetHeight;
                } else {
                    isDragging = true;
                    offsetX = e.clientX - wrapper.offsetLeft;
                    offsetY = e.clientY - wrapper.offsetTop;
                }

                document.onmousemove = (e) => {
                    if (isResizing) {
                        const diffX = e.clientX - startX;
                        const diffY = e.clientY - startY;

                        imgWidth = startWidth + diffX;
                        imgHeight = startHeight + diffY;

                        wrapper.style.width = `${imgWidth}px`;
                        wrapper.style.height = `${imgHeight}px`;
                    }

                    if (isDragging) {
                        imgX = e.clientX - offsetX;
                        imgY = e.clientY - offsetY;

                        wrapper.style.left = `${imgX}px`;
                        wrapper.style.top = `${imgY}px`;
                    }
                };

                document.onmouseup = () => {
                    isDragging = false;
                    isResizing = false;
                    document.onmousemove = null;
                };
            };
        }

        async function downloadModifiedPDF() {
            const imgBytes = await fetch(imgElement.src).then(res => res.arrayBuffer());
            const img = await pdfDoc.embedPng(imgBytes);

            const pages = pdfDoc.getPages();
            const firstPage = pages[0];

            firstPage.drawImage(img, {
                x: imgX,
                y: pdfHeight - imgY - imgHeight,
                width: imgWidth,
                height: imgHeight
            });

            const blob = new Blob([await pdfDoc.save()], { type: "application/pdf" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "modified.pdf";
            link.click();
        }
    </script>
</body>
</html>
