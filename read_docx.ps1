Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead("z:\AdminWeb\FinalProject_ClinicWEB - PHP\Aura_Clinic_Current_Version_Detailed_Documentation.docx")
$entry = $zip.GetEntry("word/document.xml")
$stream = $entry.Open()
$reader = New-Object System.IO.StreamReader($stream)
$xmlStr = $reader.ReadToEnd()
$reader.Close()
$zip.Dispose()
$xmlStr -replace '<[^>]+>', ' ' | Out-File -FilePath "doc_output.txt" -Encoding utf8
