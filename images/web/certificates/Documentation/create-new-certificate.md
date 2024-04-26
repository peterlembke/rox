# Create a new certificate

With rox you get a certificate: dev.local.crt that you can use.
If that is enough for you then you do not need to read this documentation.

Below is how you create your own self-signed certificates just like dev.local is.

You might have added several different domains that point to 127.0.0.1 in your /etc/hosts file and now you want certificates for them.

## About HTTPS
A certificate is required to get HTTPS.  
A certificate gets installed when you start the Docker environment.  
You can update the certificate with the rox command  

```
rox certificate update {your.domain.com} 
```

Read more [here](https://www.labeightyfour.com/2019/07/27/generate-keys-in-openssl-using-configuration-file/)
And the guide [here](https://www.charzam.com/?p=1455)

## errors

Go into the Docker box for the web server
```
rox shell web root 
```

In the Docker box you can see what config is loaded for each domain.
```
apache2ctl -S
```

You can see details on the certificates
``` 
openssl s_client -connect dev.local:443
```

### ERR_CONNECTION_REFUSED
If you get an issue like ERR_CONNECTION_REFUSED in your browser then check if it works inside the box first.

Go into the box
```
rox shell web root
```
Install some good to have command
```
apt update
apt install nano
apt install telnet
```
and run the telnet command
``` 
telnet dev.local 443
```
It should connect.

### ERR_SSL_PROTOCOL_ERROR
If you get an issue like ERR_SSL_PROTOCOL_ERROR in your browser then the SSL works so far.
See [here](https://stackoverflow.com/questions/63188013/localhost-sent-an-invalid-response-even-though-i-use-a-self-signed-certificate)

Commands
```
curl -Lkv https://dev.local
curl -Lkv http://dev.local
nc -v dev.local 443
nc -v 127.0.0.1 80 
```
I expect the http and 80 to work. Try this command
``` 
nc -v dev.local 443
```
If the connection works then SSL works. The next command will show the real error 
```
curl -Lkv https://dev.local
```
I got this error
```
*   Trying 127.0.0.1:443...
* TCP_NODELAY set
* Connected to dev.local (127.0.0.1) port 443 (#0)
* ALPN, offering h2
* ALPN, offering http/1.1
* successfully set certificate verify locations:
*   CAfile: /etc/ssl/certs/ca-certificates.crt
  CApath: /etc/ssl/certs
* TLSv1.3 (OUT), TLS handshake, Client hello (1):
* error:1408F10B:SSL routines:ssl3_get_record:wrong version number
* Closing connection 0
curl: (35) error:1408F10B:SSL routines:ssl3_get_record:wrong version number
```
This [solution](https://stackoverflow.com/questions/50840101/curl-35-error1408f10bssl-routinesssl3-get-recordwrong-version-number)