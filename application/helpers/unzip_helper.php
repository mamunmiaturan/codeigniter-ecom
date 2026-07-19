<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : unzip_helper.php
 */

function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true) 
{
  if ($zip = zip_open($src_file)) 
  {
    if ($zip) 
    {
      $splitter = ($create_zip_name_dir === true) ? "." : "/";
      if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
      
      // Create the directories to the destination dir if they don't already exist
      create_dirs($dest_dir);

      // For every file in the zip-packet
      while ($zip_entry = zip_read($zip)) 
      {
        $entry_name = zip_entry_name($zip_entry);
        
        // Mitigate path traversal (Zip Slip)
        if (strpos($entry_name, '..') !== false) {
            continue;
        }

        // Mitigate RCE: block execution extensions
        $ext = strtolower(pathinfo($entry_name, PATHINFO_EXTENSION));
        if (in_array($ext, array('php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'htaccess', 'phar', 'exe', 'sh', 'pl', 'py', 'cgi'))) {
            continue;
        }

        // Now we're going to create the directories in the destination directories
        
        // If the file is not in the root dir
        $pos_last_slash = strrpos($entry_name, "/");
        if ($pos_last_slash !== false)
        {
          // Create the directory where the zip-entry should be saved (with a "/" at the end)
          create_dirs($dest_dir.substr($entry_name, 0, $pos_last_slash+1));
        }

        // Open the entry
        if (zip_entry_open($zip,$zip_entry,"r")) 
        {
          
          // The name of the file to save on the disk
          $file_name = $dest_dir.$entry_name;
          
          // Check if the files should be overwritten or not
          if ($overwrite === true || $overwrite === false && !is_file($file_name))
          {
            // Get the content of the zip entry
            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            if(!is_dir($file_name)){file_put_contents($file_name, $fstream );}
            // Set the rights
            //chmod($file_name, 0755);
          }
          
          // Close the entry
          zip_entry_close($zip_entry);
        }       
      }
      // Close the zip-file
      zip_close($zip);
    }
  } 
  else
  {
    return false;
  }
  
  return true;
}

/**
 * This function creates recursive directories if it doesn't already exist
 *
 * @param String  The path that should be created
 *  
 * @return  void
 */
function create_dirs($path)
{
  if (!is_dir($path))
  {
    $directory_path = "";
    $directories = explode("/",$path);
    array_pop($directories);
    
    foreach($directories as $directory)
    {
      $directory_path .= $directory."/";
      if (!is_dir($directory_path))
      {
        mkdir($directory_path);
        //chmod($directory_path, 0755);
      }
    }
  }
}

