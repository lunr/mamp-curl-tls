# Upgrading cURL Library for MAMP 3.4 and 3.5

PCI DSS compliance is moving to require TLS 1.1 and 1.2 for all SSL / TLS connections between client and server in June 2016. Payment gateways such as PayPal and Authorize.net are requiring TLS 1.2 for applications to use their payment gateways. PayPal is forcing TLS 1.2, Authorize.net will accept TLS 1.1 for June 2016.

MAMP 3.4 and 3.5 includes the cURL library which is used by PHP for its cURL functions. Unfortunately, the cURL library uses the OpenSSL library that is built into Mac OS X. As of OS X 10.11 El Capitan, the OpenSSL library is still out of date with version 0.9.8, which does not support TLS 1.2.

The steps outlined here will upgrade the MAMP cURL library to OpenSSL v1.0.2 which supports TLS 1.2 and will allow you to support PayPal's new TLS requirements. The upgraded cURL library will be based on the OpenSSL packaged with the homebrew package manager and cURL from the offical website, haxx.se.

**Disclaimer: I have minimally tested this. But I know that it works in PHP 5.4 based on the test code included at the end of this document. All PHP cURL requests work successfully but I do not know what other subsystems of MAMP may be affected by using this custom compiled version of the cURL library.**

## Upgrade Steps

### 1) Make sure you have XCode command line tools intsalled.

We are going to need a C compiler and other libraries to upgrade cURL. So fire up a Terminal which you will continue to use for each step in this documentation.

    xcode-select --install

### 2) Install Homebrew's OpenSSL library

The goal is to compile cURL against the OpenSSL library offered by http://brew.sh, so if you do not have homebrew installed yet, follow the instructions on their website or, with caution, run this command:

    /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

Next, install the OpenSSL library:

    brew install openssl

### 3) Check the cURL version included with MAMP

We just want to confirm which version of cURL we are using because we want to download and install the same version from the offical cURL website, haxx.se

    /Applications/MAMP/Library/bin/curl-config --version

The command should return with version `7.43.0`

**Make sure to quit MAMP before completing the next steps!**

### 4) Download cURL source

Download cURL's source code from the official site at curl.haxx.se/download or fetch it directly:

    cd ~/Downloads
    wget https://curl.haxx.se/download/curl-7.43.0.tar.gz

Next, extract the tarball and cd into the working directory

    tar xzvf curl-7.43.0.tar.gz
    cd curl-7.43.0

### 5) Download CA / Certificate bundles and extract into MAMP

cURL by default does not come with any CA files or bundles. You can find your own source, or download the `ca-bundle.tgz` file from this repository (https://github.com/lunr/mamp-curl-tls) and extract into MAMP:

    tar xzvf ca-bundle.tgz -C /Applications/MAMP

### 6) Compile cURL

Execute the following `configure` command in the working directory of the cURL source code:

    ./configure --prefix=/Applications/MAMP/Library --with-ssl=/usr/local/Cellar/openssl/1.0.2g --with-ca-path=/Applications/MAMP/etc/openssl/certs --with-ca-bundle=/Applications/MAMP/etc/openssl/certs/ca-bundle.crt

This command is written specifically to build against homebrew's OpenSSL library and the CA bundle you downloaded in step 5. You can add your own options if you wish.

Once the command is complete, you should have output exactly like this:

    curl version:     7.43.0
    Host setup:       x86_64-apple-darwin15.4.0
    Install prefix:   /Applications/MAMP/Library
    Compiler:         gcc
    SSL support:      enabled (OpenSSL)
    SSH support:      no      (--with-libssh2)
    zlib support:     enabled
    GSS-API support:  no      (--with-gssapi)
    TLS-SRP support:  enabled
    resolver:         default (--enable-ares / --enable-threaded-resolver)
    IPv6 support:     enabled
    Unix sockets support: enabled
    IDN support:      no      (--with-{libidn,winidn})
    Build libcurl:    Shared=yes, Static=yes
    Built-in manual:  enabled
    --libcurl option: enabled (--disable-libcurl-option)
    Verbose errors:   enabled (--disable-verbose)
    SSPI support:     no      (--enable-sspi)
    ca cert bundle:   /Applications/MAMP/etc/openssl/certs/ca-bundle.crt
    ca cert path:     /Applications/MAMP/etc/openssl/certs
    LDAP support:     enabled (OpenLDAP)
    LDAPS support:    enabled
    RTSP support:     enabled
    RTMP support:     no      (--with-librtmp)
    metalink support: no      (--with-libmetalink)
    HTTP2 support:    disabled (--with-nghttp2)
    Protocols:        DICT FILE FTP FTPS GOPHER HTTP HTTPS IMAP IMAPS LDAP LDAPS POP3 POP3S RTSP SMB SMBS SMTP SMTPS TELNET TFTP

Specifically, review the lines `Install prefix` and `ca cert path` and `ca cert bundle` and confirm they are pointing to MAMP's directory.

If so, continue, else, something was incorrect about the `configure` command

### 7) Install new cURL library

    make && make install

### 8) Restart MAMP and confirm OpenSSL version

Open the MAMP application and start the servers. You can use `phpinfo()` to confirm the OpenSSL version under the `curl` section of `phpinfo()`. It should read `SSL Version: OpenSSL/1.0.2g`

## How to Test

Now that we have upgraded the cURL library, we need to test our connection with PayPal's payment gateway API to make sure MAMP and cURL will support TLS 1.2.

Create a PHP script in the root directory of one of your virtual hosts, and paste the following code:

    <?php

    $curl_version = curl_version();
    echo 'SSL Version: ' . $curl_version['ssl_version'] . "\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://tlstest.paypal.com/");
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if(!$response) { print_r(curl_error($ch)); } else { echo $response; }

    ?>

Open the PHP script's URL in a web browser, and you should see output like this:

    SSL Version: OpenSSL/1.0.2g
    PayPal_Connection_OK

_If the output doesn't match, then your MAMP curl library didn't get updated successfully._
