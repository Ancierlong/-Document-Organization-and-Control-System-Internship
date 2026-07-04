<?php

session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if POST data exists before accessing it
// This prevents errors if someone tries to access this page directly without POST data
if (!isset($_POST['id']) || !isset($_POST['uploaded_at'])) {
    // Handle the case where required data is not provided
    // You might redirect them back or show an error message
    // For now, we'll exit with an error.
    exit('Error: Required data not provided.');
}

// Store relevant data in session with better names
$_SESSION['concept_paper_id'] = (int)$_POST['id']; // The ID from conceptpapers table
$_SESSION['uploaded_timestamp'] = $_POST['uploaded_at']; // Assuming this is a unique identifier/timestamp string for logging
$_SESSION['user_id_for_log'] = $_SESSION['user_id']; // Pass user ID explicitly for logging if needed later

require '../db_connect.php'; // Assume this file establishes $conn and handles connection errors

$pdfPath = "";
$conceptPaperId = $_SESSION['concept_paper_id'];

// Use $conn from db_connect.php
if ($conn->connect_error) {
     // Handle database connection error gracefully
     error_log("Database Connection Error: " . $conn->connect_error); // Log the error server-side
     exit('Database connection failed.'); // Display a generic error to the user
}

// Retrieve file_name from conceptpapers table using prepared statement
$stmt = $conn->prepare("SELECT file_name FROM conceptpapers WHERE id = ?");
if ($stmt === false) {
     error_log("Database Prepare Error: " . $conn->error); // Log the error
     exit('Database statement preparation failed.'); // Display a generic error
}
$stmt->bind_param("i", $conceptPaperId);
$stmt->execute();
$stmt->bind_result($file_name);
$stmt->fetch();
$stmt->close(); // Close the statement

// Check if file exists in the folder and validate its path
if (!empty($file_name)) {
    $baseDir = realpath("../files_concept_papers/");
    $filePath = "../files_concept_papers/" . $file_name;
    $realTarget = realpath($filePath);

    // Validate that the resolved path is within the allowed directory
    if ($realTarget && $baseDir && strpos($realTarget, $baseDir) === 0 && file_exists($realTarget)) {
        $pdfPath = $filePath;
    } else {
        // File exists in DB record but not in the expected secure location or invalid path
        echo "<script>alert('Error: File not found or invalid path!');</script>";
        // Consider logging this security relevant event
    }
} else {
    echo "<script>alert('Error: No record found for the given ID!');</script>";
}

$conn->close(); // Close the database connection when done in this script
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Editor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
    <script>
        // Set the workerSrc for PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js";
    </script>
    <style>
        /* --- Optimized and Merged Styles --- */
        body {
            font-family: Arial, sans-serif;
            background-color: #7F1416; /* Original background */
            margin: 0; /* Remove default body margin */
            padding: 20px; /* Add padding instead */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh; /* Ensure body takes at least full viewport height */
            box-sizing: border-box; /* Include padding in element's total width and height */
            flex-direction: column; /* Stack children vertically */
        }

        .editor-container { /* Renamed from login-container */
            background-color: #fff;
            border-radius: 8px; /* Slightly larger border radius */
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15); /* Improved shadow */
            padding: 30px;
            width: 100%; /* Make container responsive */
            max-width: 800px; /* Set a max width for better layout on large screens */
            box-sizing: border-box;
            text-align: center; /* Center text within the container */
            margin-bottom: 20px; /* Add space below container */
        }

        h2 {
            text-align: center;
            margin-bottom: 25px; /* Increased margin */
            color: #333;
        }

        h4 {
            color: blue;
            margin-top: 0; /* Remove default margin top */
            margin-bottom: 15px; /* Add some space below */
        }

        /* Combined button and link styles */
        button, .button {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s ease, opacity 0.3s ease; /* Added opacity for disabled state */
            cursor: pointer;
            margin: 5px;
            font-size: 16px;
        }

        .button { /* Specific style for the "Return" link */
            background-color: #4CAF50;
            color: #fff;
        }

        .button:hover {
            background-color: #3d8b40;
        }

        button { /* Specific style for editor buttons */
            background-color: #007BFF;
            color: #fff;
        }

        button:hover:not(:disabled) { /* Hover effect only when not disabled */
            background-color: #0056b3;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }


        input[type="file"] {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            cursor: pointer;
            display: block; /* Make it a block element */
            margin-left: auto; /* Center the input */
            margin-right: auto; /* Center the input */
            width: fit-content; /* Adjust width to fit content */
        }

         #page-controls {
             margin-bottom: 20px;
         }


        #page-display {
            display: inline-block;
            margin: 0 15px;
            font-weight: bold;
            color: #333;
            min-width: 80px; /* Give it a minimum width to prevent jumping */
        }

        #pdf-container {
            position: relative;
            margin: 20px auto;
            border: 1px solid #ccc;
            overflow: hidden;
            /* Add max-width and height to control its size within the container */
            max-width: 100%; /* Ensure it doesn't exceed container width */
            height: auto; /* Allow height to adjust based on canvas */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Add slight shadow */
            /* The dimensions will be set dynamically by JavaScript based on the PDF page */
        }

         #pdf-container canvas {
             display: block;
             width: 100%; /* Make canvas fill the container width */
             height: auto; /* Maintain aspect ratio */
         }


        .image-wrapper {
            position: absolute;
            cursor: move;
            transform-origin: center;
            border: 2px dashed #007BFF;
            box-sizing: border-box; /* Include border in element's total size */
        }

        .image-wrapper img {
            display: block;
            width: 100%;
            height: 100%;
            pointer-events: none; /* Allow clicking/dragging the wrapper, not the image */
        }

        .resize-handle {
            width: 15px;
            height: 15px;
            background: #007BFF;
            position: absolute;
            bottom: -8px;
            right: -8px;
            cursor: se-resize;
            border-radius: 50%;
            z-index: 10; /* Ensure handle is above image */
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9); /* Slightly less transparent */
            display: flex; /* Keep as flex for centering */
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 9999;
            pointer-events: none; /* Allow clicks to pass through when hidden */
            opacity: 0; /* Start hidden */
            visibility: hidden; /* Start hidden */
            transition: opacity 0.3s ease, visibility 0.3s ease; /* Smooth transition */
        }

        #loading-overlay.visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto; /* Prevent clicks when visible */
        }


        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #007BFF;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>
<div class="editor-container"> <h2>PDF Editor with Image Controls</h2>

    <h4 style="color: blue; ">Upload PNG file only!</h4>
    <input type="file" id="image-upload" accept="image/png"> <a href="view_concept_papers_test.php" class="button">Return</a>

    <div id="page-controls">
        <button id="prev-page" disabled>Previous</button>
        <span id="page-display">Page: 1 / ?</span> <button id="next-page" disabled>Next</button>
    </div>

    <button id="update-pdf" disabled>Update PDF</button> <div id="pdf-container">
        </div>
</div>

<div id="loading-overlay">
    <div class="spinner"></div>
    <p>Loading...</p> </div>

<script>
    // Wrap code in an IIFE for better scope management
    (function() {
        let pdfDoc = null; // PDFLib document object (for modification)
        let pdfjsDoc = null; // PDF.js document object (for rendering)
        let pdfBytes = null; // Array buffer of the original PDF bytes

        let canvas = null; // The canvas element for rendering
        let ctx = null; // The 2D context of the canvas

        let imgElement = null; // The actual <img> element
        let wrapper = null; // The draggable/resizable div wrapper around the image

        let currentImageState = { // Object to hold the state of the *currently displayed* image wrapper
            x: 50, // Position relative to pdf-container (px)
            y: 50,
            width: 150, // Size (px)
            height: 150,
            aspectRatio: 1, // Calculated aspect ratio of the image
            src: '' // Data URL or Blob URL of the image
        };

         let currentPageIndex = 0; // 0-based index of the current page being viewed

        // Get DOM elements
        const pdfContainer = document.getElementById("pdf-container");
        const pageDisplay = document.getElementById("page-display");
        const prevPageBtn = document.getElementById("prev-page");
        const nextPageBtn = document.getElementById("next-page");
        const updatePdfBtn = document.getElementById("update-pdf");
        const imageUploadInput = document.getElementById("image-upload");
        const loadingOverlay = document.getElementById('loading-overlay');
        const loadingMessage = loadingOverlay.querySelector('p');

        // PHP variable containing PDF path (Passed from server)
        const pdfFilePath = "<?php echo $pdfPath; ?>";

        // --- Helper Functions ---

        // Function to show the loading overlay with an optional message
        function showLoading(message = 'Loading...') {
            loadingMessage.textContent = message;
            loadingOverlay.classList.add('visible');
        }

        // Function to hide the loading overlay
        function hideLoading() {
            loadingOverlay.classList.remove('visible');
        }

        // Function to update the state of page navigation and update buttons
        function updateButtonStates() {
            if (pdfjsDoc) {
                prevPageBtn.disabled = currentPageIndex <= 0;
                nextPageBtn.disabled = currentPageIndex >= pdfjsDoc.numPages - 1;
                updatePdfBtn.disabled = !imgElement || !pdfBytes; // Disable update if no image or PDF loaded
            } else {
                // No PDF loaded
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
                updatePdfBtn.disabled = true;
            }
        }

        // --- PDF Loading and Rendering ---

        // Function to load the PDF from the server
        async function loadPDF(filePath) {
            if (!filePath) {
                alert("PDF file path is not available.");
                updateButtonStates();
                return;
            }
            showLoading('Loading PDF...');
            try {
                const res = await fetch(filePath);
                if (!res.ok) {
                    // Handle HTTP errors (e.g., 404 not found)
                     const errorText = await res.text();
                     throw new Error(`HTTP error! status: ${res.status} - ${errorText}`);
                }
                pdfBytes = await res.arrayBuffer();

                // Load with PDFLib (needed for adding image and saving)
                pdfDoc = await PDFLib.PDFDocument.load(pdfBytes);

                // Load with PDF.js (needed for rendering to canvas)
                const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
                pdfjsDoc = await loadingTask.promise;

                // Render the first page initially
                renderPDFPage(currentPageIndex);

            } catch (error) {
                console.error("Error loading PDF:", error);
                alert("Failed to load PDF: " + error.message);
                // Reset state on error
                pdfDoc = null;
                pdfjsDoc = null;
                pdfBytes = null;
                 pdfContainer.innerHTML = ""; // Clear container
                 if (wrapper) wrapper.remove(); // Remove image wrapper if it was added
                 wrapper = null;
                 imgElement = null;
                 pageDisplay.textContent = 'Page: ? / ?'; // Reset page display

            } finally {
                hideLoading(); // Always hide loading overlay
                updateButtonStates(); // Update buttons based on final state
            }
        }

        // Function to render a specific PDF page to the canvas
        async function renderPDFPage(pageIndex) {
            if (!pdfjsDoc) return; // Do nothing if PDF.js document is not loaded

            showLoading(`Rendering page ${pageIndex + 1}...`);

            try {
                // Clear previous content in the container
                pdfContainer.innerHTML = "";

                 // Re-create the canvas for the new page
                canvas = document.createElement("canvas");
                pdfContainer.appendChild(canvas);
                ctx = canvas.getContext("2d");

                const page = await pdfjsDoc.getPage(pageIndex + 1); // PDF.js pages are 1-based
                const viewport = page.getViewport({ scale: 1 }); // Get the viewport at scale 1 to get original dimensions

                // Set canvas dimensions to match the PDF page dimensions
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                // Set container size to match canvas/page size
                pdfContainer.style.width = `${viewport.width}px`;
                pdfContainer.style.height = `${viewport.height}px`;

                // Configure the rendering context
                const renderContext = {
                    canvasContext: ctx,
                    // Render the viewport at a scale that fits the canvas (which matches the page dimensions)
                    viewport: page.getViewport({ scale: canvas.width / viewport.width })
                };

                // Render the page
                await page.render(renderContext).promise;

                // Update the page display text
                pageDisplay.textContent = `Page: ${pageIndex + 1} / ${pdfjsDoc.numPages}`;

                // If an image was previously added, re-add its wrapper to the *newly rendered* page view
                // Note: This re-adds it at the *last known position*, not a page-specific stored position.
                 if (imgElement && currentImageState.src) {
                    addImageToEditor(currentImageState.src, currentImageState.x, currentImageState.y, currentImageState.width, currentImageState.height);
                 }

            } catch (error) {
                console.error("Error rendering page:", error);
                alert("Failed to render page.");
                 // Optionally reset to a default view or previous page on error
            } finally {
                hideLoading(); // Always hide loading overlay
                 updateButtonStates(); // Update buttons
            }
        }

        // Function to navigate to the previous page
        function previousPage() {
            if (pdfjsDoc && currentPageIndex > 0) {
                currentPageIndex--;
                renderPDFPage(currentPageIndex);
            }
        }

        // Function to navigate to the next page
        function nextPage() {
            if (pdfjsDoc && currentPageIndex < pdfjsDoc.numPages - 1) {
                currentPageIndex++;
                renderPDFPage(currentPageIndex);
            }
        }

        // --- Image Handling (Upload, Drag, Resize) ---

        // Function to add the image element and its wrapper to the editor view
        function addImageToEditor(imgSrc, x, y, width, height) {
            // Remove any existing wrapper before adding a new one
            if (wrapper) {
                wrapper.remove();
            }

            wrapper = document.createElement("div");
            wrapper.classList.add("image-wrapper");
            // Set initial position and size using the provided values
            wrapper.style.left = `${x}px`;
            wrapper.style.top = `${y}px`;
            wrapper.style.width = `${width}px`;
            wrapper.style.height = `${height}px`;

            imgElement = new Image();
            imgElement.src = imgSrc;

            // Calculate aspect ratio once the image has loaded
            imgElement.onload = () => {
                currentImageState.aspectRatio = imgElement.naturalWidth / imgElement.naturalHeight;
                 // Adjust height based on calculated aspect ratio if using default width
                 if (width === 150 && height === 150) { // Check if defaults were used
                      currentImageState.height = currentImageState.width / currentImageState.aspectRatio;
                      wrapper.style.height = `${currentImageState.height}px`;
                 }
                 updateButtonStates(); // Enable update button now that image is ready
            };

            // Handle image loading errors
            imgElement.onerror = () => {
                console.error("Failed to load image:", imgSrc);
                alert("Failed to load the selected image.");
                // Clean up elements and state if image fails to load
                if (wrapper) wrapper.remove();
                wrapper = null;
                imgElement = null;
                currentImageState = { x: 50, y: 50, width: 150, height: 150, aspectRatio: 1, src: '' }; // Reset state
                updateButtonStates();
            };


            wrapper.appendChild(imgElement);

            const resizeHandle = document.createElement("div");
            resizeHandle.classList.add("resize-handle");
            wrapper.appendChild(resizeHandle);

            pdfContainer.appendChild(wrapper);

            // Add drag and resize functionality to the new wrapper
            addDragAndResize(wrapper, resizeHandle);

            // Update the current image state object
            currentImageState.src = imgSrc;
            currentImageState.x = x;
            currentImageState.y = y;
            currentImageState.width = width;
            currentImageState.height = height;

            updateButtonStates(); // Re-evaluate button states (e.g., enable update PDF button)
        }


        // Function to add drag and resize functionality to the image wrapper
        function addDragAndResize(wrapper, resizeHandle) {
            let isResizing = false;
            let startX, startY, startWidth, startHeight, startMouseX, startMouseY;

            const onMouseDown = (e) => {
                e.preventDefault(); // Prevent default browser drag behavior

                if (e.target === resizeHandle) {
                    isResizing = true;
                    startMouseX = e.clientX;
                    startMouseY = e.clientY;
                    startWidth = wrapper.offsetWidth;
                    startHeight = wrapper.offsetHeight;
                } else {
                    isResizing = false;
                    startMouseX = e.clientX;
                    startMouseY = e.clientY;
                    startX = wrapper.offsetLeft; // Position relative to its offsetParent (pdf-container)
                    startY = wrapper.offsetTop;
                }

                // Add listeners to the document to ensure movement is tracked even if mouse leaves the wrapper
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            };

            const onMouseMove = (e) => {
                if (isResizing) {
                    const diffX = e.clientX - startMouseX;

                    // Calculate new width and height maintaining aspect ratio
                    let newWidth = Math.max(20, startWidth + diffX); // Minimum size 20px
                    let newHeight = newWidth / currentImageState.aspectRatio;

                    // Constrain resizing within the bounds of the PDF container
                    const maxPossibleWidth = pdfContainer.offsetWidth - wrapper.offsetLeft;
                    const maxPossibleHeight = pdfContainer.offsetHeight - wrapper.offsetTop;

                    newWidth = Math.min(newWidth, maxPossibleWidth);
                    // Recalculate height based on the potentially constrained width to maintain aspect ratio
                    newHeight = newWidth / currentImageState.aspectRatio;

                     // Also constrain based on height if needed (less common with aspect ratio)
                     if (newHeight > maxPossibleHeight) {
                          newHeight = maxPossibleHeight;
                          newWidth = newHeight * currentImageState.aspectRatio; // Recalculate width
                     }
                     // Ensure newWidth is still at least 20 after height constraint check
                     newWidth = Math.max(20, newWidth);
                     newHeight = newWidth / currentImageState.aspectRatio; // Final height check based on final width


                    wrapper.style.width = `${newWidth}px`;
                    wrapper.style.height = `${newHeight}px`;

                    // Update the current image state with the new dimensions
                    currentImageState.width = newWidth;
                    currentImageState.height = newHeight;

                } else { // Dragging
                    const diffX = e.clientX - startMouseX;
                    const diffY = e.clientY - startMouseY;

                    let newX = startX + diffX;
                    let newY = startY + diffY;

                    // Constrain dragging within the bounds of the PDF container
                    newX = Math.max(0, Math.min(newX, pdfContainer.offsetWidth - wrapper.offsetWidth));
                    newY = Math.max(0, Math.min(newY, pdfContainer.offsetHeight - wrapper.offsetHeight));

                    wrapper.style.left = `${newX}px`;
                    wrapper.style.top = `${newY}px`;

                    // Update the current image state with the new position
                    currentImageState.x = newX;
                    currentImageState.y = newY;
                }
            };

            const onMouseUp = () => {
                isResizing = false; // Reset resizing flag
                // Remove listeners from the document
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            // Add the initial mousedown listener to the wrapper
            wrapper.addEventListener('mousedown', onMouseDown);
        }

        // --- PDF Modification and Saving ---

        // Function to create the modified PDF and send it to the server
        // (Inside the IIFE in pdffinalv2.php's <script> block)

// ... other variables and functions ...

 // Function to create the modified PDF and send it to the server
 async function downloadModifiedPDF() {
     // Basic checks to ensure PDF, image element, PDFLib doc, and PDF.js doc are loaded
     if (!pdfBytes || !imgElement || !pdfDoc || !pdfjsDoc) {
         alert("Error: PDF or image not loaded!");
         return;
     }

     showLoading('Updating PDF...');

     try {
         // Create a new PDFLib document from the original bytes
         const modifiedDoc = await PDFLib.PDFDocument.load(pdfBytes);

         // Fetch and embed the image into the PDFLib document
         const imgBytes = await fetch(currentImageState.src).then(res => res.arrayBuffer());
         const pngImage = await modifiedDoc.embedPng(imgBytes); // Assuming PNG

         // Get the current page object from the modified PDFLib document (PDFLib uses 0-based index)
         const currentPage = modifiedDoc.getPages()[currentPageIndex];
         // Get the actual dimensions of the current page in PDF units (points)
         const { width: pageWidth_pdf, height: pageHeight_pdf } = currentPage.getSize();

         // --- Crucial Step: Coordinate and Size Conversion ---
         // We need to convert the position and size stored in browser pixels (from the wrapper)
         // to PDF units (points) that PDFLib's drawImage expects.

         // Get the current rendering canvas element.
         const renderCanvas = pdfContainer.querySelector('canvas');
         if (!renderCanvas) {
             throw new Error("Rendering canvas not found. Cannot determine scale.");
         }

         // Get the viewport of the current page from the PDF.js document at scale 1.
         // This gives us the page dimensions in PDF units (points).
         // Using pdfjsDoc (the rendering document) is necessary because it's what rendered the canvas we see.
         // FIX: Use getPage() method correctly
         const pageViewportAt1 = await pdfjsDoc.getPage(currentPageIndex + 1).then(page => page.getViewport({ scale: 1 }));
         const pdfUnitWidth_at_scale1 = pageViewportAt1.width; // Width of the page in PDF units (points)

         // Calculate the scale factor: How many browser pixels correspond to one PDF unit (point)?
         // This is the ratio of the canvas's *displayed* width to its *intrinsic* PDF unit width.
         const scale = renderCanvas.offsetWidth / pdfUnitWidth_at_scale1;

         // Get current image position and size in browser pixels from state
         const imgWidth_px = currentImageState.width;
         const imgHeight_px = currentImageState.height;
         const imgX_px = currentImageState.x; // X position from the left edge of the container in browser pixels
         const imgY_px = currentImageState.y; // Y position from the top edge of the container in browser pixels

         // Convert pixel dimensions to PDF units (points)
         const imgWidth_pdf = imgWidth_px / scale;
         const imgHeight_pdf = imgHeight_px / scale;

         // Convert pixel position (from top-left origin in browser) to PDF units (from top-left origin)
         const imgX_pdf_top_left = imgX_px / scale;
         const imgY_pdf_top_left = imgY_px / scale;

         // Convert PDF units (from top-left origin) to PDF units (from bottom-left origin)
         // PDF Y (bottom-left origin) = Page Height (PDF units) - (Image Y from Top (PDF units) + Image Height (PDF units))
         const pdfX = imgX_pdf_top_left; // X coordinate is the same relative to the left edge
         const pdfY = pageHeight_pdf - (imgY_pdf_top_left + imgHeight_pdf);

         // --- Draw the image using the converted coordinates and dimensions ---
         currentPage.drawImage(pngImage, {
             x: pdfX,
             y: pdfY,
             width: imgWidth_pdf,
             height: imgHeight_pdf,
         });

         // --- Save and Send the Modified PDF ---
         const pdfBytesNew = await modifiedDoc.save();
         const blob = new Blob([pdfBytesNew], { type: "application/pdf" });

         // Prepare data for the server request
         const formData = new FormData();
         formData.append("pdf", blob, "modified.pdf"); // Append the new PDF blob
         formData.append("file_path", pdfFilePath); // Send the original file path so the server knows which file to replace

         // Send the request to your PHP script (update_pdf.php)
         const response = await fetch("update_pdf.php", {
             method: "POST",
             body: formData
         });

          hideLoading(); // Hide loading overlay before showing alerts/redirect

         // Assuming update_pdf.php returns JSON
         const result = await response.json();

         if (result.success) {
             alert(result.message || "PDF updated successfully!"); // Show success message from server
             // Redirect the user after successful update
             window.location.href = "view_concept_papers_test.php";
         } else {
             // Handle server-side errors returned in the JSON response
             console.error("Server Error:", result.message);
             alert("Error updating PDF: " + (result.message || "An unknown error occurred."));
             // Stay on the page to allow the user to try again or inspect
         }

     } catch (error) {
         // Handle any JavaScript or network errors during the process
         console.error("Error during PDF update:", error);
         alert("An error occurred while updating the PDF: " + error.message);
          hideLoading(); // Ensure loading overlay is hidden in case of JS errors
     }
 }

// ... rest of the script (event listeners, initial load) ...

        // --- Event Listeners ---

        // Attach event listeners to buttons and input
        prevPageBtn.addEventListener('click', previousPage);
        nextPageBtn.addEventListener('click', nextPage);
        updatePdfBtn.addEventListener('click', downloadModifiedPDF);

        imageUploadInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) {
                 // User cancelled file selection
                 return;
            }

            // Validate file type client-side (server-side validation is also crucial)
             if (file.type !== 'image/png') {
                 alert("Please upload a PNG file.");
                 imageUploadInput.value = ''; // Clear the input field
                 return;
             }


            const reader = new FileReader();
            reader.readAsDataURL(file); // Read file as a data URL

            reader.onloadstart = () => showLoading('Reading image...');
            reader.onloadend = () => hideLoading();

            reader.onload = () => {
                // Add the loaded image to the editor view
                // Use default starting position and size for a new image
                addImageToEditor(reader.result, 50, 50, 150, 150);
            };

             reader.onerror = (error) => {
                 console.error("FileReader error:", error);
                 alert("Error reading the image file.");
                 hideLoading();
             };
        });

        // --- Initial Load ---

        // Load the PDF when the page loads
        loadPDF(pdfFilePath);

    })(); // End of IIFE
</script>

</body>
</html>