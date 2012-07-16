<?php

namespace PS\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SetupCommand extends Command
{
  
  protected $workspace_path = '/Users/toonketels/workspace';
  protected $websites_path = '/applications/mamp/htdocs';
  protected $profile_path = '/Users/toonketels/.zshrc';
  protected $drush_alias_path = '/Users/toonketels/.drush/aliases.drushrc.php';
  
  protected $input;
  protected $output;
  
  protected $filesystem;
  protected $dialog;
  
  protected $hosts_path = '/etc/hosts';
  //protected $apache_conf_path = '/Applications/MAMP/conf/apache/httpd-conf';
  protected $apache_conf_path = '/private/etc/apache2/extra/httpd-vhosts.conf';
  
  protected function configure()
  {
    /**
     * Usage:
     *
     * ./console setup name="new_project"
     */
    $this->setName('setup')
         ->setDescription('Setup of local project environment.')
         ->addArgument('name', InputArgument::REQUIRED, 'The projects name.')
         ->addArgument('domain', InputArgument::REQUIRED, 'The website domain name.');
  }
  
  protected function getFilesystem()
  {
    if($this->filesystem) return $this->filesystem;
    
    return $this->filesystem = new Filesystem();
    
  }
  
  protected function getDialog()
  {
    if($this->dialog) return $this->dialog;
    
    return $this->dialog = $this->getHelperSet()->get('dialog');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;
    
    $name = $input->getArgument('name');
    $domain = $input->getArgument('domain');
    
    $workspace = $this->createProjectDir($name);
    $workspace_website_root = $this->createSymlink($workspace, $name);
    $this->createDrushAlias($name, $workspace_website_root, $domain);
    $this->createVirtialHost($workspace_website_root, $domain);

  }
  
  protected function createProjectDir($name)
  {
    if (!$this->getDialog()->askConfirmation($this->output, sprintf('<question>Create a project named %s in default localion?</question>', $name), false)) {
      return;
    }
    
    $new_project_dir = sprintf('%s/%s', $this->workspace_path, $name);
    
    $ret = $this->getFilesystem()->mkdir($new_project_dir);
    
    if($ret) {
      $this->output->writeln(sprintf('<info>The directory "%s" has been created</info>', $new_project_dir));
      return $new_project_dir;
    } else {
      $this->output->writeln(sprintf('<error>Could not create directoy "%s"</error>', $new_project_dir));
    }
  }
  
  protected function createSymlink($workspace, $name)
  {
    $targetDir = sprintf('%s/%s', $this->websites_path, $name);
    $originDir = sprintf('%s/www', $workspace);
    
    if (!$this->getDialog()->askConfirmation($this->output,
                                             sprintf('<question>Link form %s named %s</question>', $originDir, $targetDir),
                                             FALSE)) {
      return;
    }
    
    /// Create origindir if it does not exists.
    $this->getFilesystem()->mkdir($originDir);    
    
    // @todo: check if done and report
    $this->getFilesystem()->symlink($originDir, $targetDir);
    
    return $targetDir;
  }
  
  protected function createDrushAlias($name, $workspace_website_root, $domain)
  {
    if (!$this->getDialog()->askConfirmation($this->output,
                                             sprintf('<question>Create drush alias @%s for %s</question>', $name, $name),
                                             FALSE)) {
      return;
    }
    
    // Open file in append mode
    $fh = fopen($this->drush_alias_path, 'a');
    
    fputs($fh, "\n");
    fputs($fh, sprintf('// Drush alias for %s', $name)."\n");
    fputs($fh, sprintf('$aliases["%s"]["root"] = "%s";', $name, $workspace_website_root)."\n");
    fputs($fh, sprintf('$aliases["%s"]["uri"] = "%s";', $name, $domain)."\n");
    fclose($fh);
    
    $this->output->writeln(sprintf('<info>Created drush alias @%s for %s.</info>', $name, $name));
  }
  
  protected function createVirtialHost($workspace_website_root, $domain)
  {
    // Add domain to hosts so domain name points to current machine
    $fh = fopen($this->hosts_path, 'a');
    fputs($fh, "\n");
    fputs($fh, sprintf('# Added by PS')."\n");
    fputs($fh, sprintf('127.0.0.1 %s', $domain)."\n");
    fclose($fh);
    
    // Config apache to know which document root applies
    $fh = fopen($this->apache_conf_path, 'a');
    fputs($fh, "\n");
    $contents = <<<APACHE
<Directory "$workspace_website_root">
Allow From All
AllowOverride All
</Directory>
<VirtualHost *:80>
ServerName "$domain"
DocumentRoot "$workspace_website_root"
</VirtualHost>
APACHE;
    fputs($fh, $contents."\n");
    fclose($fh);
  }
  
  
}
