<?php
// Get an instance of the class
$atomBackup = new AtomBackup();

// Elaborate Command Line Argoument
if($argc === 1) {
  $atomBackup->printUsage();
} 
else 
{
  switch ($argv[1]) {
    case '--backup':
      $atomBackup->makeAtomBackup();
      break;
    case '--restore':
      $atomBackup->restoreAtomBackup();
      break;
	case '--reinstall':
		$arg = NULL;
		if(isset($argv[2])){ $arg = $argv[2]; }
		$atomBackup->reinstallPackages($arg);
		break;
    default:
      // nothing...
      break;
  }
}


/**
 * [AtomBackup Class]
 */
class AtomBackup
{

  public $user_home = "";
  public $atom_dot_dir = "";
  public $atom_files = [];
  public $backup_folder = "";

  function __construct() {
    $this->user_home = $_SERVER['HOME'];
    $this->atom_dot_dir = $this->user_home . DIRECTORY_SEPARATOR . ".atom";
    $this->backup_folder = dirname(__FILE__) . DIRECTORY_SEPARATOR ."backup";
    $this->atom_files = [
      "config.cson",
      "github.cson",
      "init.coffee",
      "keymap.cson",
      "projects.cson",
      "snippets.cson",
      "styles.less"
    ];
  }


  public function printUsage() {
    echo
      "\n|-------------------- Atom Backup Usage --------------------" .
      "\n| - Make backup:" .
      "\n|     $ php atom-backup.php --backup" .
      "\n| - Restore backup:" .
      "\n|     $ php atom-backup.php --restore" .
	  "\n| - Reinstall packages (can pass a file or will use default file location):" .
      "\n|     $ php atom-backup.php --reinstall [packages-list-file]" .
      "\n|-----------------------------------------------------------\n";
  }


  public function makeAtomBackup()
  {
    // Store Package List into a File
    if(file_exists($this->atom_dot_dir)) {
      $package_list = shell_exec("apm list --installed --bare");
      file_put_contents($this->backup_folder . DIRECTORY_SEPARATOR . "atom-package-list.txt", $package_list);
    } else {
      echo ".atom folder does not exists\n";
    }

    // Store a Copy of Important Configuration Files
    foreach ($this->atom_files as $file_name) {
      $source_file = $this->atom_dot_dir . DIRECTORY_SEPARATOR . $file_name;
      $dest_file = $this->backup_folder . DIRECTORY_SEPARATOR .  $file_name;
      copy($source_file, $dest_file);
    }
  }

  public function restoreAtomBackup()
  {
	$this->reinstallPackages();
	$this->restoreConfigurationFiles();
  }
  
  
  public function reinstallPackages($packageListFile="") 
  {
	// ReStore Package List into a File
    if(is_dir($this->atom_dot_dir))
    {
      $packages_array = file($this->backup_folder . DIRECTORY_SEPARATOR . "atom-package-list.txt");
	  if($packageListFile!="") { $packages_array = $packageListFile; }

      foreach ($packages_array as $package_name_with_version) {
          $at_pos = strpos($package_name_with_version, "@");
          $package_name = "";
          if($at_pos !== FALSE) {
            $package_name = substr($package_name_with_version, 0, $at_pos);
          } else {
            $package_name = $package_name_with_version;
          }
          $package_dir = $this->atom_dot_dir . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $package_name;
          if(!file_exists($package_dir)) {
              echo shell_exec("apm install " . $package_name);
          }
      }

    } else {
      echo ".atom folder does not exists\n";
    }
  }
  
  
  public function restoreConfigurationFiles() {
    // Restore a Copy of Important Configuration Files
    foreach ($this->atom_files as $file_name) {
      $source_file = $this->backup_folder . DIRECTORY_SEPARATOR . $file_name;
      $dest_file = $this->atom_dot_dir . DIRECTORY_SEPARATOR .  $file_name;
      copy($source_file, $dest_file);
    } 
  }
  
}
