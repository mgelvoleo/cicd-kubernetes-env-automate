Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.ssh.insert_key = false
  
  # Enable time sync for all VMs to prevent clock drift issues
  config.vm.provider "virtualbox" do |vb|
    vb.gui = false
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
  end

  # Controller node
  config.vm.define "controller" do |controller|
    controller.vm.hostname = "controller"
    controller.vm.network "private_network", ip: "192.168.56.10"
    controller.vm.provider "virtualbox" do |vb|
      vb.name = "controller"
      vb.memory = 2048
      vb.cpus = 2
    end

    controller.vm.provision "shell", inline: <<-SHELL
      # Fix time sync issue first
      echo "Fixing time synchronization..."
      sudo timedatectl set-ntp false
      sudo apt-get update 2>/dev/null || true
      sudo apt-get install -y ntpdate curl
      sudo ntpdate -s time.nist.gov
      sudo timedatectl set-ntp true
      
      # Generate SSH key for controller if not exists
      if [ ! -f /home/vagrant/.ssh/id_rsa ]; then
        ssh-keygen -t rsa -b 4096 -N "" -f /home/vagrant/.ssh/id_rsa
        chown vagrant:vagrant /home/vagrant/.ssh/id_rsa*
      fi
      
      # Copy public key to /vagrant folder for other nodes
      cp /home/vagrant/.ssh/id_rsa.pub /vagrant/ 2>/dev/null || true
      
      # Add host entries
      echo "192.168.56.11 master"  | sudo tee -a /etc/hosts
      echo "192.168.56.12 worker1" | sudo tee -a /etc/hosts
      echo "192.168.56.13 worker2" | sudo tee -a /etc/hosts
      echo "192.168.56.10 controller" | sudo tee -a /etc/hosts
    SHELL
  end

  # Function to add controller's pubkey to other nodes
  def add_pubkey(node, hostname, ip)
    node.vm.hostname = hostname
    node.vm.network "private_network", ip: ip
    node.vm.provider "virtualbox" do |vb|
      vb.name = "k8s-#{hostname}"
      vb.memory = 2048
      vb.cpus = 2
    end

    node.vm.provision "shell", inline: <<-SHELL
      # Fix time sync issue first
      echo "Fixing time synchronization for #{hostname}..."
      sudo timedatectl set-ntp false
      sudo apt-get update 2>/dev/null || true
      sudo apt-get install -y ntpdate curl
      sudo ntpdate -s time.nist.gov
      sudo timedatectl set-ntp true
      
      # Ensure SSH directory exists
      mkdir -p /home/vagrant/.ssh
      chmod 700 /home/vagrant/.ssh
      
      # Copy controller's public key (shared via /vagrant folder)
      if [ -f /vagrant/id_rsa.pub ]; then
        cat /vagrant/id_rsa.pub >> /home/vagrant/.ssh/authorized_keys
        chmod 600 /home/vagrant/.ssh/authorized_keys
        chown -R vagrant:vagrant /home/vagrant/.ssh
      else
        echo "Warning: Controller public key not found in /vagrant/"
      fi
      
      # Add host entries
      echo "192.168.56.10 controller" | sudo tee -a /etc/hosts
      echo "192.168.56.11 master"    | sudo tee -a /etc/hosts
      echo "192.168.56.12 worker1"   | sudo tee -a /etc/hosts
      echo "192.168.56.13 worker2"   | sudo tee -a /etc/hosts
    SHELL
  end

  # Master node
  config.vm.define "master" do |master|
    add_pubkey(master, "master", "192.168.56.11")
    master.vm.provider "virtualbox" do |vb|
      vb.memory = 3072
    end
  end

  # Worker1
  config.vm.define "worker1" do |worker|
    add_pubkey(worker, "worker1", "192.168.56.12")
  end

  # Worker2
  config.vm.define "worker2" do |worker|
    add_pubkey(worker, "worker2", "192.168.56.13")
  end
end