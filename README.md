<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://github.com/prominic/saas-autoinstall-vagrant-packer-example/">
    <img src="conf/wiki/images/Prom.jpg" alt="Logo" width="200" height="100">
  </a>

  <h3 align="center">Docova Vagrant Build</h3>

  <p align="center">
    An README to jumpstart your build of the Docova Bundle
    <br />
    <a href="https://github.com/prominic/saas-autoinstall-vagrant-packer-example/"><strong>Explore the docs »</strong></a>
    <br />
    <br />
    <a href="https://github.com/prominic/saas-autoinstall-vagrant-packer-example/">View Demo</a>
    ·
    <a href="https://github.com/prominic/saas-autoinstall-vagrant-packer-example/issues">Report Bug</a>
    ·
    <a href="https://github.com/prominic/saas-autoinstall-vagrant-packer-example/issues">Request Feature</a>
  </p>
</p>



<!-- TABLE OF CONTENTS -->
## Table of Contents

* [About the Project](#saas-autoinstall-vagrant-packer-example)
  * [Built With](#built-with)
* [Getting Started](#getting-started)
  * [Prerequisites](#prerequisites)
  * [Installation](#downloading-docova-project-to-a-local-folder)
    * [Mac OS X](https://github.com/prominic/saas-autoinstall-vagrant-packer-example/blob/master/MacMojaveReadme.md) -- Quick Start
    * [Windows](https://github.com/prominic/saas-autoinstall-vagrant-packer-example/blob/master/Win10ReadMe.md) -- Quick Start
* [Rebuilding](#rebuilding-the-project)
* [Roadmap](#roadmap)
* [Contributing](#contributing)
* [License](#license)
* [Contact](#authors)
* [Acknowledgements](#acknowledgments)



# saas-autoinstall-vagrant-packer-example
Primary goal is to use Vagrant on Windows, Mac, and Linux to deploy the latest Docova Bundle in an Ubuntu 18.04 VM, in order to run regression tests and automating installation via API call from Docova Web Services.

We will keep the Master branch as the core of the project and any application that we want to Automate/Regression Test we will branch in order to compartmentalize the project for use with mutiple applications.
 
## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. You MUST have the Vagrant, Virtualbox and Git installed on your machine. Please follow the instructions below for setting up your VM with the pre-requistes. 

### Prerequisites

You will need some software on your PC or Mac:

```
git
Vagrant
Virtualbox
```

### Installing

To ease deployment, we have a few handy scripts that will utlize a package manager for each OS to get the pre-requisite software for your host OS. This is NOT required, this is to help you ensure you have all the applications that are neccessary to run this VM.

#### Windows
Powershell has a package manager named Chocalatey which is very similar to SNAP, YUM, or other Package manager, We will utilize that to quickly install Virtualbox, Vagrant and Git.

Powershell
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
choco install vagrant
choco install virtualbox
choco install git.install
```

For those that need to run this in a Command Prompt, you can use this:

CMD
```bat
@"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -InputFormat None -ExecutionPolicy Bypass -Command "iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))" && SET "PATH=%PATH%;%ALLUSERSPROFILE%\chocolatey\bin"
choco install vagrant
choco install virtualbox
choco install git.install
```

#### Mac
Just like Windows and other Linux repos, there is a similar package manager for Mac OS X, Homebrew, We will utilize that to install the prequsites. You will likley need to allow unauthenticated applications in the Mac OS X Security Settings, there are reports that Mac OS X Mojave will require some additional work to get running correctly. You do NOT have to use these scripts to get the pre-requisites on your Mac, it is recommened, you simply need to make sure you have the 3 applications installed on your Mac.

```shell
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew cask install virtualbox
brew cask install vagrant
brew cask install vagrant-manager
brew install git
```

#### CentOS 7
We will utilize YUM and a few other bash commands to get the Virtualbox, Git,  and Vagrant installed.

YUM
```shell
yum -y install gcc dkms make qt libgomp patch kernel-headers kernel-devel binutils glibc-headers glibc-devel font-forge
cd /etc/yum.repo.d/
wget http://download.virtualbox.org/virtualbox/rpm/rhel/virtualbox.repo
yum install -y VirtualBox-5.1
/sbin/rcvboxdrv setup
yum -y install https://releases.hashicorp.com/vagrant/1.9.6/vagrant_1.9.6_x86_64.rpm
sudo yum install git
```

#### Ubuntu
We will utilize APT to get the Virtualbox, Git,  and Vagrant installed.

APT
```shell
sudo apt-get install virtualbox vagrant git-core -y 
```

## Downloading the Docova Project to a Local folder

Open up a terminal and perform the following git command in order to save the Project to a local folder:

```shell
git clone https://github.com/prominic/saas-autoinstall-vagrant-packer-example.git

```
### Configuring the Environment
Once you have navigated into the projects directory. You will need to modify the Hosts.yml to your specific Environment.
Please set the configuration file with the correct, Network and Memory and CPU settings your host machine will allow, as these may vary from system to system, I cannot predict your Machines CPU and Network Requirements. You will need to make sure that you do not over allocate CPU, and RAM. In regards to Networking, you MUST change the networking, you will need to set the IP to that of one that is not in use by any other machine on your network.

If you want to change to a different branch for different Application Builds, change the branch variable to that of an existing branch in this repo, in the Hosts.yml

```
cd saas-autoinstall-vagrant-packer-example
vi Hosts.yml
```
![GitHub Logo](/conf/wiki/images/hosts.yml.png)

#### Commonly Changed Parameters:

* ip: Use any IP on your Internal Network that is NOT in use by any other machine.
* gateway: This is the IP of your Router
* identifier: This is the Hostname of the VM, make sure this is a Fully Qualified Domain Name
* mac: This is your machines Network unique identifier, if you run more than one instance on a network, randonmize this. [Mac Generator](https://www.miniwebtool.com/mac-address-generator/)
* netmask: Set this to the subnet you have your network configured to. This is normally: 255.255.255.0
* name: The Vagrant unique identifier
* cpu: The number of cores you are allocating to this machine. Please beware and do not over allocate. Overallocation may cause instability
* memory: The amount of Memory you are allocating to the machine.  Please beware and do not over allocate. Overallocation may cause instability


Once you have configured the Hosts.yml file. You should now be set to go on getting the VM up and running.

### Starting the VM
The installation process is estimated to take about 15 - 30 Minutes. 

```
vagrant up
```

#### Example of a Succesful run:

<details><summary>Details:</summary>
<p>

#### Example of a Succesful run:

```powershell
 vagrant up
Bringing machine 'Docova-2-new' up with 'virtualbox' provider...
==> Docova-2-new: Checking if box 'peru/ubuntu-18.04-server-amd64' version '20191101.01' is up to date...
==> Docova-2-new: Running provisioner: shell...
    Docova-2-new: Running: /tmp/vagrant-shell20191113-8943-8ant21.sh
    Docova-2-new: A Branch is not specified, Skipping addition of branch
==> Docova-2-new: Running provisioner: shell...
    Docova-2-new: Running: /tmp/vagrant-shell20191113-8943-6obskj.sh
    Docova-2-new: alias ..='cd ..'
    Docova-2-new: alias ...='cd ../..'
    Docova-2-new: alias h='cd ~'
    Docova-2-new: alias c='clear'
    Docova-2-new: alias ll='ls -la'
    Docova-2-new: Reading package lists...
    Docova-2-new: Building dependency tree...
    Docova-2-new:
    Docova-2-new: Reading state information...
    Docova-2-new: python-apt is already the newest version (1.6.4).
    Docova-2-new: The following packages were automatically installed and are no longer required:
    Docova-2-new:   apache2 apache2-bin apache2-data apache2-utils dbconfig-common
    Docova-2-new:   dbconfig-mysql javascript-common libapr1 libaprutil1 libaprutil1-dbd-sqlite3
    Docova-2-new:   libaprutil1-ldap libjs-jquery libjs-sphinxdoc libjs-underscore liblua5.2-0
    Docova-2-new:   libpcre2-8-0 libsodium23
    Docova-2-new: Use 'sudo apt autoremove' to remove them.
    Docova-2-new: 0 upgraded, 0 newly installed, 0 to remove and 29 not upgraded.
==> Docova-2-new: Running provisioner: ansible_local...
    Docova-2-new: Running ansible-playbook...

PLAY [all] *********************************************************************

TASK [Gathering Facts] *********************************************************
ok: [Docova-2-new]

TASK [Ensuring Aptitude is installed.] *****************************************
ok: [Docova-2-new]

TASK [debug] *******************************************************************
ok: [Docova-2-new] => {
    "msg": "run_letsencrypt"
}

TASK [nginx : Update apt cache] ************************************************
ok: [Docova-2-new]

TASK [nginx : Install nginx] ***************************************************
ok: [Docova-2-new]

TASK [nginx : Start nginx] *****************************************************
ok: [Docova-2-new]

TASK [nginx : Update nginx confs for Docova + PHP] *****************************
ok: [Docova-2-new]

TASK [nginx : Enable site] *****************************************************
ok: [Docova-2-new]

TASK [mysql : Install mysql] ***************************************************
changed: [Docova-2-new]

TASK [mysql : Start the MySQL service] *****************************************
ok: [Docova-2-new]

TASK [mysql : Set root user password] ******************************************
ok: [Docova-2-new] => (item=docova-new.int.dc-01.m4kr.net)
ok: [Docova-2-new] => (item=127.0.0.1)
ok: [Docova-2-new] => (item=:1)
ok: [Docova-2-new] => (item=localhost)

TASK [mysql : Copy .my.cnf file with root password credentials] ****************
ok: [Docova-2-new]

TASK [mysql : Create mysql user] ***********************************************
changed: [Docova-2-new]

TASK [mysql : Create mysql database] *******************************************
ok: [Docova-2-new]

TASK [mysql : Create mysql database] *******************************************
changed: [Docova-2-new]

TASK [mysql : Delete anonymous MySQL server user for $server_hostname] *********
ok: [Docova-2-new]

TASK [mysql : Delete anonymous MySQL server user for localhost] ****************
ok: [Docova-2-new]

TASK [mysql : Remove the MySQL test database] **********************************
ok: [Docova-2-new]

TASK [mysql : Update mysql root password for all root accounts] ****************
ok: [Docova-2-new] => (item=docova-new.int.dc-01.m4kr.net)
ok: [Docova-2-new] => (item=127.0.0.1)
ok: [Docova-2-new] => (item=:1)
ok: [Docova-2-new] => (item=localhost)

TASK [php : Add repository for PHP 7.1] ****************************************
ok: [Docova-2-new]

TASK [php : Purge PHP version packages (besides the currently chosen php_version).] ***
changed: [Docova-2-new]

TASK [php : Also purge php-common package if any versions were just purged.] ***
ok: [Docova-2-new]

TASK [php : Update all packages to the latest version] *************************
changed: [Docova-2-new]

TASK [php : Setup php-fpm] *****************************************************
changed: [Docova-2-new]

TASK [php : Add php settings] **************************************************
ok: [Docova-2-new]

TASK [elasticsearch212 : Ensure Java is installed.] ****************************
ok: [Docova-2-new]

TASK [elasticsearch212 : Adding Elasticsearch Repository Key for Version 2.1.2] ***
ok: [Docova-2-new]

TASK [elasticsearch212 : Adding Elasticsearch Repository for Version 2.1.2] ****
ok: [Docova-2-new]

TASK [elasticsearch212 : Installing Elasticsearch] *****************************
ok: [Docova-2-new]

TASK [elasticsearch212 : Enable and Start ElasitcSearch] ***********************
ok: [Docova-2-new]

TASK [elasticsearch212 : Disabling Elasitcsearch from Updating] ****************
ok: [Docova-2-new]

TASK [www-data : Create webroot] ***********************************************
ok: [Docova-2-new]

TASK [www-data : Check if Docova directory exists in /var/html/www/docova] *****
ok: [Docova-2-new]

TASK [www-data : Copying Docova Bundle Zip] ************************************
ok: [Docova-2-new]

TASK [www-data : Extract Docova] ***********************************************
skipping: [Docova-2-new]

TASK [www-data : Move Docova install files] ************************************
skipping: [Docova-2-new]

TASK [www-data : Create document_root] *****************************************
ok: [Docova-2-new]

TASK [www-data : Check if Docova directory exists in /var/docova/attachments/] ***
ok: [Docova-2-new]

TASK [www-data : Set Docova Document Permissions] ******************************
changed: [Docova-2-new] => (item=/var/html/www/docova/src/Docova/DocovaBundle/Agents)
changed: [Docova-2-new] => (item=/var/html/www/docova/src/Docova/DocovaBundle/Resources/public/css/custom)
changed: [Docova-2-new] => (item=/var/html/www/docova/src/Docova/DocovaBundle/Resources/images)
changed: [Docova-2-new] => (item=/var/html/www/docova/src/Docova/DocovaBundle/Resources/public/js/custom)
changed: [Docova-2-new] => (item=/var/html/www/docova/src/Docova/DocovaBundle/Resources/views/DesignElements)
changed: [Docova-2-new] => (item=/var/html/www/docova/var/logs)
changed: [Docova-2-new] => (item=/var/html/www/docova/var/sessions)
changed: [Docova-2-new] => (item=/var/html/www/docova/var/cache)
changed: [Docova-2-new] => (item=/var/html/www/docova/web/upload)
changed: [Docova-2-new] => (item=/var/docova/attachments/)

TASK [www-data : Update Docova config file] ************************************
ok: [Docova-2-new] => (item={u'regexp': u'database_driver:', u'line': u'    database_driver: pdo_mysql'})
ok: [Docova-2-new] => (item={u'regexp': u'database_port:', u'line': u'    database_port: 3306'})
changed: [Docova-2-new] => (item={u'regexp': u'database_name:', u'line': u'    database_name: database_name'})
changed: [Docova-2-new] => (item={u'regexp': u'database_user:', u'line': u'    database_user: database_user'})
changed: [Docova-2-new] => (item={u'regexp': u'database_password:', u'line': u'    database_password: database_password'})

TASK [www-data : Setting Autoindex Cron Job] ***********************************
ok: [Docova-2-new]

TASK [www-data : Setting DailyLifeCycle Cron Job] ******************************
ok: [Docova-2-new]

TASK [www-data : Running setUpDOCOVA] ******************************************
changed: [Docova-2-new]

TASK [www-data : Running setUpDOCOVA] ******************************************
changed: [Docova-2-new]

TASK [letsencrypt : Upgrade System] ********************************************

changed: [Docova-2-new]

TASK [letsencrypt : Add certbot repository] ************************************
changed: [Docova-2-new]

TASK [letsencrypt : Install Certbot's Nginx package] ***************************
changed: [Docova-2-new]

TASK [letsencrypt : Check if certificate already exists.] **********************
ok: [Docova-2-new] => (item={u'servername': u'docova-new.int.dc-01.m4kr.net', u'documentroot': u'/var/html/www/docova/web'})

TASK [letsencrypt : Stop services to allow certbot to generate a cert.] ********
changed: [Docova-2-new] => (item=nginx)

TASK [letsencrypt : Generate new] **********************************************
changed: [Docova-2-new] => (item={'failed': False, u'stat': {u'exists': False}, 'ansible_loop_var': u'item', 'item': {u'servername': u'docova-new.int.dc-01.m4kr.net', u'documentroot': u'/var/html/www/docova/web'}, u'invocation': {u'module_args': {u'follow': False, u'get_checksum': True, u'path': u'/etc/letsencrypt/live/docova-new.int.dc-01.m4kr.net/cert.pem', u'checksum_algorithm': u'sha1', u'get_md5': False, u'get_mime': True, u'get_attributes': True}}, u'changed': False})

TASK [letsencrypt : Start services after cert has been generated.] *************
changed: [Docova-2-new] => (item=nginx)

TASK [letsencrypt : Enabling SSL in Nginx] *************************************
changed: [Docova-2-new] => (item={u'regexp': u'ssl_certificate  /etc/letsencrypt/live/docova-new.int.dc-01.m4kr.net/fullchain.pem;', u'line': u'    #ssl_certificate'})
changed: [Docova-2-new] => (item={u'regexp': u'ssl_certificate_key /etc/letsencrypt/live/docova-new.int.dc-01.m4kr.net/privkey.pem;', u'line': u'    #ssl_certificate_key'})
changed: [Docova-2-new] => (item={u'regexp': u'ssl_protocols TLSv1 TLSv1.1 TLSv1.2;', u'line': u'    #ssl_protocols'})
changed: [Docova-2-new] => (item={u'regexp': u'ssl_ciphers HIGH:!aNULL:!MD5;', u'line': u'    #ssl_ciphers'})

RUNNING HANDLER [www-data : restart php7.1-fpm] ********************************
changed: [Docova-2-new]

RUNNING HANDLER [letsencrypt : restart nginx] **********************************
changed: [Docova-2-new]

PLAY RECAP *********************************************************************
Docova-2-new               : ok=52   changed=19   unreachable=0    failed=0    skipped=2    rescued=0    ignored=0
```
</p>
</details>


During this process the VM will restart twice, You will be promopted if you have multiple network cards, to select and active network card on your system.

## Rebuilding the Project

There are times that the the project may be misconfigured by a typo or wrong value in the Hosts.yml or due to networking issues. As a result of this the VM may not be setup correctly. 

Vagrant commands are highly dependent on the path that your run the commands in. When you run *vagrant up*, you must be inside of the Project folder. If you need to restart the VM, you can do so by running *vagrant reload* however, for this to work, you must be inside of the Vagrant Project folder.


| Common vagrant commands | Modifiers and Options | Default Action                                                  | With Option                                                   |
|-------------------------|-----------------------|-----------------------------------------------------------------|---------------------------------------------------------------|
| vagrant up              | --provision           | Boots the VM, First run Provisions, Subsequent runs simply boot | If the VM has already been created, will run the provisioners |
| vagrant reload          | --provision           | Restarts the VM without Provisioning                            | Restarts the VM runs the provisioners                         |
| vagrant destroy         | -f                    | Vagrant Asks to Destroy VM                                      | Vagrant Destroys the VM                                       |
| vagrant global-status   | --prune               | Lists all VMs and their project paths                           | Lists all VM and their project paths and removes corrupt VMs  |
| vagrant halt            |                       | Shuts down the VM                                               |                                                               |
## Roadmap

See the [open issues](https://github.com/prominic/saas-autoinstall-vagrant-packer-example/issues) for a list of proposed features (and known issues).

## Built With
* [Vagrant](https://www.vagrantup.com/) - Portable Development Environment Suite.
* [VirtualBox](https://www.virtualbox.org/wiki/Downloads) - Hypervisor.
* [Ansible](https://www.ansible.com/) - Virtual Manchine Automation Management.
* [vagrant-vbguest](https://github.com/dotless-de/vagrant-vbguest) - A Vagrant plugin to keep your VirtualBox Guest Additions up to date.
* [vagrant-reload](https://github.com/aidanns/vagrant-reload) - A Vagrant plugin that allows you to reload a Vagrant plugin as a provisioning step.
* [vagrant-disksize](https://github.com/sprotheroe/vagrant-disksize) - A Vagrant plugin to resize disks in VirtualBox.

## Contributing

Please read [CONTRIBUTING.md](https://www.prominic.net) for details on our code of conduct, and the process for submitting pull requests to us.

## Authors

* **Mark Gilbert** - *Initial work* - [Makr91](https://github.com/Makr91)

See also the list of [contributors](https://github.com/prominic/saas-autoinstall-vagrant-packer-example/graphs/contributors) who participated in this project.

## License

This project is licensed under the SSLP v3 License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
