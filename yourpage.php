<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" media="all"/>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/jquery.qrcode.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <!-- <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <title>Your Page - Invicikey</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1 class="text-center">Your Page</h1>
    <hr>
    <div class="container">
        <?php
            session_start();
            $sessionid = session_id();
        ?>
    </div>
    <div class="d-flex justify-content-center">
        <div class="container" id="bluetoothPairing">
            <p>Pair your bluetooth to unlock this page</p>
            <div class="row">
                <div class="col">
                    <button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>
                </div>
                <div class="col">
                    <div class="clearfix" id="loading">
                        <div class="spinner-grow text-primary float-right" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container" id="safePage">
        <hr>
            <p id="secret"> LOCKED </p>
        <hr>
    </div>
    <div class="row justify-content-center">
        <button type="button" class="btn btn-danger" id="logout" onclick="signOut()">Sign Out</button>
    </div>
</body>
</html>

<script>
    var bluetoothDevice;
    var sessionid = "<?php echo $sessionid; ?>";
    let unique = 0;
    var wait;
    var loadingIndicator = document.getElementById("loading");
    loadingIndicator.style.display = "none";

    function onInactive(ms, cb) {
        wait = setTimeout(cb, ms);
        document.onmousemove = document.mousedown = document.mouseup = document.onkeydown = document.onkeyup = document.focus = function () {
            clearTimeout(wait);
            wait = setTimeout(cb, ms);
        };
    }

    function signOut(){
        <?php
            $username=$_SESSION['username'];
            include '../keyforce/phpseclib/Crypt/Hash.php';
            include '../keyforce/constants.php';
            require_once('../keyforce/connections.php');

            $sql = 'SELECT salt FROM credential WHERE username="'.$username.'"';
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                while($row = $result->fetch_assoc()) {
                    $saltDatabase = $row['salt'];
                }
            } else {
                $saltDatabase = NULL;
            }

            $hash = new Crypt_Hash('sha1');
            $authenticated = bin2hex($hash->hash($STRINGFALSE.$saltDatabase.$PEPPER));
            $sql = "UPDATE credential SET authenticated='".$authenticated."' WHERE username='".$username."'";
            if (mysqli_query($conn, $sql)) {
                $status = 1;
                $message = "reset success";
            } else {
                $status = 0;
                $message = "reset failed";
            }
            mysqli_close($conn);
            session_regenerate_id();
            session_unset();
            session_destroy();
        ?>
        alert("Signing out");
        if(onDisconnect()){
            window.open("sign_in.php","_self");
        }
    }

    function pairing(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'authpairstart','sessionid':sessionid},
            success: function(data){
                console.log(data);
            },
            error: function(){
                alert("error");
            }
        });

        navigator.bluetooth.requestDevice({acceptAllDevices: true})
        .then(device => {
            loadingIndicator.style.display = "block";
            bluetoothDevice = device;
            console.log(device.name);
            console.log("connecting...");        
            return device.gatt.connect();
        })
        .then(server => {
            console.log("getting service...");
            return server.getPrimaryService('device_information');
        })
        .then(service => {
            console.log("getting characteristic...");
            if('system_id'){
                return service.getCharacteristics('system_id');
            }
            return service.getCharacteristics();
        })
        .then(characteristics => {
            let queue = Promise.resolve();
            let decoder = new TextDecoder('utf-8');
            characteristics.forEach(characteristic => {
                switch (characteristic.uuid) {
                    case BluetoothUUID.getCharacteristic('system_id'):
                        queue = queue.then(_ => characteristic.readValue()).then(value => {
                            unique = padHex(value.getUint8(7)) + padHex(value.getUint8(6)) + padHex(value.getUint8(5));
                            console.log(unique);
                            checkIdBle();
                        });
                    break;
                    default: console.log('> Unknown Characteristic: ' + characteristic.uuid);
                }
            });
            return queue;
        })
        .catch(error => { console.log(error); loadingIndicator.style.display = "none";
        });
    }

    function checkIdBle(){
        //if unique = macble in database then trigger connect, give info connection
        //if unique != macble in database then give info not authenticated
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'checkIdBle','sessionid':sessionid,'idBle':unique},// !!!! please change to session id
            success: function(data){
                console.log(data);
                if(data){
                    console.log('> BLE connected. Page Unlocked');
                    loadingIndicator.style.display = "none";
                    document.getElementById("secret").innerHTML = "UNLOCKED"; 
                    //unlock page
                    $.ajax({
                        type: 'post',
                        url: "http://localhost/keyforce/auth_force.php",
                        dataType: 'json',
                        data:{'func':'authpairend','sessionid':sessionid},
                        success: function(data){
                            console.log(data);
                        },
                        error: function(){
                            alert("error");
                        }
                    });
                    // timer for check inactivity user for 10 seconds 
                    onInactive(10000, function () {
                        if(onDisconnect()){
                            console.log('> BLE disconnected. Page Locked');
                            document.getElementById("secret").innerHTML = "LOCKED";
                        }
                    });
                }else{
                    alert("Can't unlock the page, not authenticated");
                }
            },
            error: function(){
                alert("error");
            }
        });
    }
    
    function onDisconnect(){
        if (!bluetoothDevice) {
            console.log('No Bluetooth Device...');
        }else{
            console.log('Disconnecting from Bluetooth Device...');
            if (bluetoothDevice.gatt.connected) {
                bluetoothDevice.gatt.disconnect();
                console.log('> Bluetooth Device has been disconnected');
            } else {
                console.log('> Bluetooth Device is already disconnected');
            }

            // clear timeout if hasbeen disconnected
            clearTimeout(wait);
        }
        return true;
    }

    // hex converter
    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
</script>