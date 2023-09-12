sudo apt-get remove docker docker-engine docker.io containerd runc

sudo apt-get update
sudo apt upgrade -y
sudo apt-get install ca-certificates curl gnupg -y

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
 "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
 "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" | \
 sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin -y

sudo wget https://github.com/docker/compose/releases/download/v2.7.0/docker-compose-linux-x86_64 -O /usr/local/bin/docker-compose
sudo chmod +x  /usr/local/bin/docker-compose
docker-compose -v

sudo bash ./installDocker.sh