<?php
$targetDir = "../files_concept_papers/"; // Folder where files will be stored

// Check if the folder exists; if not, create it
if (!is_dir($targetDir)) {
mkdir($targetDir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
$fileName = basename($_FILES["file"]["name"]);
$targetFilePath = $targetDir . $fileName;

// Allowed file types
$fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
$allowedTypes = ["jpg", "png", "gif", "pdf", "txt", "docx", "jfif", "WebP", "HEIF", "BMP"];

if (in_array($fileType, $allowedTypes)) {
if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
echo "File uploaded successfully to: $targetFilePath";
} else {
echo "Error uploading file.";
}
} else {
echo "Invalid file type.";
}
} else {
echo "No file uploaded.";
}
?>	