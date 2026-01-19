# FTP Upload Script for Teslim Nushasi
$ftpServer = "ftp://ftp.caygetir.net"
$ftpUser = "teslimnushasi@teslimnushasi.com"
$ftpPass = "Konya1923*"
$localPath = "C:\xampp\htdocs\teslimnushasi"
$remotePath = "/home/caygetir/teslimnushasi.com"

# Create credential
$secpasswd = ConvertTo-SecureString $ftpPass -AsPlainText -Force
$credential = New-Object System.Management.Automation.PSCredential($ftpUser, $secpasswd)

# Function to upload file
function Upload-FtpFile {
    param (
        [string]$LocalFile,
        [string]$RemoteFile
    )
    
    try {
        $uri = New-Object System.Uri("$ftpServer$RemoteFile")
        $ftpRequest = [System.Net.FtpWebRequest]::Create($uri)
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftpRequest.UseBinary = $true
        $ftpRequest.UsePassive = $true
        
        $fileContent = [System.IO.File]::ReadAllBytes($LocalFile)
        $ftpRequest.ContentLength = $fileContent.Length
        
        $requestStream = $ftpRequest.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $ftpRequest.GetResponse()
        Write-Host "Uploaded: $RemoteFile" -ForegroundColor Green
        $response.Close()
        return $true
    }
    catch {
        Write-Host "Error uploading $RemoteFile : $_" -ForegroundColor Red
        return $false
    }
}

# Function to create directory
function Create-FtpDirectory {
    param ([string]$RemoteDir)
    
    try {
        $uri = New-Object System.Uri("$ftpServer$RemoteDir")
        $ftpRequest = [System.Net.FtpWebRequest]::Create($uri)
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $response = $ftpRequest.GetResponse()
        Write-Host "Created directory: $RemoteDir" -ForegroundColor Cyan
        $response.Close()
    }
    catch {
        # Directory might already exist
    }
}

Write-Host "Starting FTP upload to $ftpServer..." -ForegroundColor Yellow

# Create directories first
$directories = @(
    "$remotePath",
    "$remotePath/api",
    "$remotePath/api/admin",
    "$remotePath/assets",
    "$remotePath/assets/css",
    "$remotePath/includes",
    "$remotePath/uploads",
    "$remotePath/uploads/temp",
    "$remotePath/Example"
)

foreach ($dir in $directories) {
    Create-FtpDirectory -RemoteDir $dir
}

# Get all files to upload (excluding .git, uploads content)
$files = Get-ChildItem -Path $localPath -Recurse -File | Where-Object {
    $_.FullName -notlike "*\.git\*" -and
    $_.FullName -notlike "*\uploads\*" -or
    $_.Name -eq ".htaccess" -or
    $_.Name -eq ".gitkeep"
}

$uploaded = 0
$failed = 0

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localPath.Length).Replace("\", "/")
    $remoteFile = "$remotePath$relativePath"
    
    if (Upload-FtpFile -LocalFile $file.FullName -RemoteFile $remoteFile) {
        $uploaded++
    } else {
        $failed++
    }
}

Write-Host ""
Write-Host "Upload complete! Uploaded: $uploaded, Failed: $failed" -ForegroundColor Green
