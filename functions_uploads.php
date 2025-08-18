<?php


function get_uploads($project_id) {
    global $conn;
    if ($project_id !== null) {
        $query = "SELECT *  FROM uploads
        WHERE project_id = :project_id 
        ORDER BY  uploads.created_at ASC";
        try {
            $uploads_query = $conn->prepare($query);
            $uploads_query->bindParam(':project_id', $project_id);
            $uploads_query->setFetchMode(PDO::FETCH_OBJ);
            $uploads_query->execute();
            $uploads_count = $uploads_query->rowCount();

            if ($uploads_count > 0) {
                $uploads =  $uploads_query->fetchAll();
                $uploads = processUploads($uploads);
            } else {
                $uploads =  [];
            }

            unset($conn);
            return $uploads;
        } catch (PDOException $err) {
            return [];
        };
    }
}



function get_uploads_of_task($task_id) {
    global $conn;
    if ($task_id !== null) {
        $query = "SELECT *  FROM uploads
        WHERE task_id = :task_id 
        ORDER BY  uploads.created_at ASC";
        try {
            $uploads_query = $conn->prepare($query);
            $uploads_query->bindParam(':task_id', $task_id);
            $uploads_query->setFetchMode(PDO::FETCH_OBJ);
            $uploads_query->execute();
            $uploads_count = $uploads_query->rowCount();

            if ($uploads_count > 0) {
                $uploads =  $uploads_query->fetchAll();
                $uploads = processUploads($uploads);
            } else {
                $uploads =  [];
            }

            unset($conn);
            return $uploads;
        } catch (PDOException $err) {
            return [];
        };
    }
}

function get_upload($upload_id = null) {
    global $conn;
    if ($upload_id != null) {

        try {
            $query = "SELECT * FROM uploads WHERE uploads.id = :id LIMIT 1";
            $upload_query = $conn->prepare($query);
            $upload_query->bindParam(':id', $upload_id);
            $upload_query->setFetchMode(PDO::FETCH_OBJ);
            $upload_query->execute();

            $upload_count = $upload_query->rowCount();

            if ($upload_count == 1) {
                $upload =  $upload_query->fetch();
                $upload =  processUpload($upload);
            } else {
                $upload = null;
            }
            unset($conn);
            return $upload;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if upload id is not greated than 0
        return null;
    }
}




function create_upload($upload) {
    global $conn;
    if (!empty($upload->project_id)  && !empty($upload->file_contents)) {



        try {


            $file_contents = $upload->file_contents;
            $filedata = explode(',', $file_contents);
            $decoded_file = base64_decode($filedata[1]); // remove the mimetype from the base 64 string


            $filename =  preg_replace('/[^a-z0-9\.]+/', '-', strtolower($upload->filename));

            $mime_type = finfo_buffer(finfo_open(), $decoded_file, FILEINFO_MIME_TYPE); // extract mime type
            $extension = mime2ext($mime_type); // extract extension from mime type


            $query = "INSERT INTO uploads (project_id, task_id, message_id, filename, extension) VALUES (:project_id, :task_id,  :message_id, :filename, :extension)";
            $upload_query = $conn->prepare($query);
            $upload_query->bindParam(':project_id', $upload->project_id);
            $upload_query->bindParam(':task_id', $upload->task_id);
            $upload_query->bindParam(':message_id', $upload->message_id);
            $upload_query->bindParam(':filename', $filename);
            $upload_query->bindParam(':extension', $extension);
            $upload_query->execute();
            $upload_id = $conn->lastInsertId();
            unset($conn);


            $target_dir = FILELOC . UPLOADDIR; // add the specific path to save the file
            mkdir($target_dir . '/' . $upload_id, 0777);

            $file_dir = $target_dir . $upload_id . '/' .  $filename;
            file_put_contents($file_dir, $decoded_file); // save


            return ($upload_id);
        } catch (PDOException $err) {

            echo json_encode($err->getMessage());
        };
    } else { // upload project_id was blank
        return false;
    }
}




function update_upload($upload_id, $upload) {
    global $conn;
    if ($upload_id > 0) {
        try {


            $updated_at =   updated_at_string();
            $query = "UPDATE uploads SET 
            `task_id` = :task_id, 
            `updated_at` = :updated_at 
            WHERE id = :id";
            $upload_query = $conn->prepare($query);
            $upload_query->bindParam(':task_id', $upload->task_id);
            $upload_query->bindParam(':updated_at', $updated_at);
            $upload_query->bindParam(':id', $upload_id);
            $upload_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // upload name was blank
        return false;
    }
}


function delete_upload($upload_id) {

    // TODO NEED TO ACTUALLY REMOVE THE FILE FROM THE SERVER

    global $conn;
    if ($upload_id > 0) {

        try {
            $query = "DELETE FROM uploads  WHERE id = :id    ";
            $upload_query = $conn->prepare($query);
            $upload_query->bindParam(':id', $upload_id);
            $upload_query->setFetchMode(PDO::FETCH_OBJ);
            $upload_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}





/*
to take mime type as a parameter and return the equivalent extension
*/
function mime2ext($mime) {
    $all_mimes = '{"png":["image\/png","image\/x-png"],"bmp":["image\/bmp","image\/x-bmp",
    "image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp",
    "image\/ms-bmp","image\/x-ms-bmp","application\/bmp","application\/x-bmp",
    "application\/x-win-bitmap"],"gif":["image\/gif"],"jpeg":["image\/jpeg",
    "image\/pjpeg"],"xspf":["application\/xspf+xml"],"vlc":["application\/videolan"],
    "wmv":["video\/x-ms-wmv","video\/x-ms-asf"],"au":["audio\/x-au"],
    "ac3":["audio\/ac3"],"flac":["audio\/x-flac"],"ogg":["audio\/ogg",
    "video\/ogg","application\/ogg"],"kmz":["application\/vnd.google-earth.kmz"],
    "kml":["application\/vnd.google-earth.kml+xml"],"rtx":["text\/richtext"],
    "rtf":["text\/rtf"],"jar":["application\/java-archive","application\/x-java-application",
    "application\/x-jar"],"zip":["application\/x-zip","application\/zip",
    "application\/x-zip-compressed","application\/s-compressed","multipart\/x-zip"],
    "7zip":["application\/x-compressed"],"xml":["application\/xml","text\/xml"],
    "svg":["image\/svg+xml"],"3g2":["video\/3gpp2"],"3gp":["video\/3gp","video\/3gpp"],
    "mp4":["video\/mp4"],"m4a":["audio\/x-m4a"],"f4v":["video\/x-f4v"],"flv":["video\/x-flv"],
    "webm":["video\/webm"],"aac":["audio\/x-acc"],"m4u":["application\/vnd.mpegurl"],
    "pdf":["application\/pdf","application\/octet-stream"],
    "pptx":["application\/vnd.openxmlformats-officedocument.presentationml.presentation"],
    "ppt":["application\/powerpoint","application\/vnd.ms-powerpoint","application\/vnd.ms-office",
    "application\/msword"],"docx":["application\/vnd.openxmlformats-officedocument.wordprocessingml.document"],
    "xlsx":["application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application\/vnd.ms-excel"],
    "xl":["application\/excel"],"xls":["application\/msexcel","application\/x-msexcel","application\/x-ms-excel",
    "application\/x-excel","application\/x-dos_ms_excel","application\/xls","application\/x-xls"],
    "xsl":["text\/xsl"],"mpeg":["video\/mpeg"],"mov":["video\/quicktime"],"avi":["video\/x-msvideo",
    "video\/msvideo","video\/avi","application\/x-troff-msvideo"],"movie":["video\/x-sgi-movie"],
    "log":["text\/x-log"],"txt":["text\/plain"],"css":["text\/css"],"html":["text\/html"],
    "wav":["audio\/x-wav","audio\/wave","audio\/wav"],"xhtml":["application\/xhtml+xml"],
    "tar":["application\/x-tar"],"tgz":["application\/x-gzip-compressed"],"psd":["application\/x-photoshop",
    "image\/vnd.adobe.photoshop"],"exe":["application\/x-msdownload"],"js":["application\/x-javascript"],
    "mp3":["audio\/mpeg","audio\/mpg","audio\/mpeg3","audio\/mp3"],"rar":["application\/x-rar","application\/rar",
    "application\/x-rar-compressed"],"gzip":["application\/x-gzip"],"hqx":["application\/mac-binhex40",
    "application\/mac-binhex","application\/x-binhex40","application\/x-mac-binhex40"],
    "cpt":["application\/mac-compactpro"],"bin":["application\/macbinary","application\/mac-binary",
    "application\/x-binary","application\/x-macbinary"],"oda":["application\/oda"],
    "ai":["application\/postscript"],"smil":["application\/smil"],"mif":["application\/vnd.mif"],
    "wbxml":["application\/wbxml"],"wmlc":["application\/wmlc"],"dcr":["application\/x-director"],
    "dvi":["application\/x-dvi"],"gtar":["application\/x-gtar"],"php":["application\/x-httpd-php",
    "application\/php","application\/x-php","text\/php","text\/x-php","application\/x-httpd-php-source"],
    "swf":["application\/x-shockwave-flash"],"sit":["application\/x-stuffit"],"z":["application\/x-compress"],
    "mid":["audio\/midi"],"aif":["audio\/x-aiff","audio\/aiff"],"ram":["audio\/x-pn-realaudio"],
    "rpm":["audio\/x-pn-realaudio-plugin"],"ra":["audio\/x-realaudio"],"rv":["video\/vnd.rn-realvideo"],
    "jp2":["image\/jp2","video\/mj2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],
    "eml":["message\/rfc822"],"pem":["application\/x-x509-user-cert","application\/x-pem-file"],
    "p10":["application\/x-pkcs10","application\/pkcs10"],"p12":["application\/x-pkcs12"],
    "p7a":["application\/x-pkcs7-signature"],"p7c":["application\/pkcs7-mime","application\/x-pkcs7-mime"],"p7r":["application\/x-pkcs7-certreqresp"],"p7s":["application\/pkcs7-signature"],"crt":["application\/x-x509-ca-cert","application\/pkix-cert"],"crl":["application\/pkix-crl","application\/pkcs-crl"],"pgp":["application\/pgp"],"gpg":["application\/gpg-keys"],"rsa":["application\/x-pkcs7"],"ics":["text\/calendar"],"zsh":["text\/x-scriptzsh"],"cdr":["application\/cdr","application\/coreldraw","application\/x-cdr","application\/x-coreldraw","image\/cdr","image\/x-cdr","zz-application\/zz-winassoc-cdr"],"wma":["audio\/x-ms-wma"],"vcf":["text\/x-vcard"],"srt":["text\/srt"],"vtt":["text\/vtt"],"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"],"csv":["text\/x-comma-separated-values","text\/comma-separated-values","application\/vnd.msexcel"],"json":["application\/json","text\/json"]}';
    $all_mimes = json_decode($all_mimes, true);
    foreach ($all_mimes as $key => $value) {
        if (array_search($mime, $value) !== false) return $key;
    }
    return false;
}


function processUpload($upload) {
    // if database is set as 1 it should return as true
    $upload->url = UPLOADDIR . $upload->id . '/' . $upload->filename;
    $upload->project_id =  intval($upload->project_id);
    $upload->task_id =  intval($upload->task_id);
    $upload->id =  intval($upload->id);
    $upload->nice_created_at = explode(' ', $upload->created_at)[0];
    $upload->is_image = ($upload->extension == 'jpeg' || $upload->extension == 'png');
    return $upload;
}


function processUploads($uploads) {

    foreach ($uploads as $upload) {
        processUpload($upload);
    }

    return $uploads;
}
