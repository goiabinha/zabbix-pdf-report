### Zabbix PDF Report
Nomeie todos os títulos de gráficos ou itens para os quais você deseja gerar um gráfico.  
Defina o escopo de um host ou grupo de hosts.  
Selecione o período como última hora, último dia, semana passada. Gere PDF.  
Eventos também podem ser incluídos no relatório.  

### Instalação
Debian/Ubuntu  
`apt update`  
`apt -y install git php-curl php-json`  
RHEL/CentOS  
`yum -y install git php-curl php-json`  
 Clone o projeto  
`cd /usr/share`  
`git clone https://github.com/goiabinha/zabbix-pdf-report.git`  
`cd zabbix-pdf-report`  
Ajuste as configurações com as credenciais do servidor zabbix  
`vi /usr/share/zabbix-pdf-report/config.inc.php`  
Ajuste as permissões das pastas e arquivos  
`chmod 755 -R /usr/share/zabbix-pdf-report/tmp`  
`chmod 755 -R /usr/share/zabbix-pdf-report/reports`  
`chown -R www-data:www-data /usr/share/zabbix-pdf-report`  
Configure o apache  
`cp /usr/share/zabbix-pdf-report/zabbix-pdf-report.conf /etc/apache2/conf-available`  
`a2enconf zabbix-pdf-report`  
`systemctl restart apache2`  

Acesse `http://IP_DO_SERVIDOR/report`  

### Código original
https://www.zabbix.com/forum/showthread.php?t=24998
