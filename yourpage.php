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
            // if(isset($_SESSION['authenticated']) && $_SESSION['authenticated']){
            //     echo "welcome";
            //     echo "<br /> Auth status ";
            //     echo $_SESSION['authenticated'];
            //     echo "<br /> Username ";
            //     echo $_SESSION['username'];
            //     echo "<br /> Auth id System ";
            //     echo $_SESSION['authenticated_id_system'];
            //     echo "<br /> Auth id DB ";
            //     echo $_SESSION['authenticated_id_database'];
            //     echo "<br /> Session ID ";
            //     echo session_id();
            // }else{
            //     echo '<script>
            //     alert("Please sign in");
            //     window.open("sign_in.php","_self");</script>';
            // }
        ?>
    </div>
    <div class="d-flex justify-content-center">
        <div class="container" id="bluetoothPairing">
            <p>Pair your bluetooth to unlock this page</p>
            <button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>
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
    var username = 'jorjyeah'; // !!!! please change to session id

    function signOut(){
        // <?php
        //     $username=$_SESSION['username'];
        //     include '../keyforce/phpseclib/Crypt/Hash.php';
        //     include '../keyforce/constants.php';
        //     require_once('../keyforce/connections.php');

        //     $sql = 'SELECT salt FROM credential WHERE username="'.$username.'"';
        //     $result = mysqli_query($conn, $sql);
        //     if (mysqli_num_rows($result) > 0) {
        //         while($row = $result->fetch_assoc()) {
        //             $saltDatabase = $row['salt'];
        //         }
        //     } else {
        //         $saltDatabase = NULL;
        //     }

        //     $hash = new Crypt_Hash('sha1');
        //     $authenticated = bin2hex($hash->hash($STRINGFALSE.$saltDatabase.$PEPPER));
        //     $sql = "UPDATE credential SET authenticated='".$authenticated."' WHERE username='".$username."'";
        //     if (mysqli_query($conn, $sql)) {
        //         $status = 1;
        //         $message = "reset success";
        //     } else {
        //         $status = 0;
        //         $message = "reset failed";
        //     }
        //     mysqli_close($conn);
        //     session_regenerate_id();
        //     session_unset();
        //     session_destroy();
        // ?>

        alert("Signing out");
        if (!bluetoothDevice) {
            return;
        }
        log('Disconnecting from Bluetooth Device...');
        if (bluetoothDevice.gatt.connected) {
            bluetoothDevice.gatt.disconnect();
        } else {
            log('> Bluetooth Device is already disconnected');
        }
        // window.open("sign_in.php","_self");
    }

    let unique = 0;

    function pairing(){
        navigator.bluetooth.requestDevice({acceptAllDevices: true})
        .then(device => {
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
        .catch(error => { console.log(error) 
        });
    }

    function checkIdBle(){
        //if unique = macble in database then trigger connect, give info connection
        //if unique != macble in database then give info not authenticated
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'checkIdBle','username':username,'idBle':unique},// !!!! please change to session id
            success: function(data){
                console.log(data);
                if(data){
                    // make listener what if bluetooth disconnected
                    bluetoothDevice.addEventListener('gattserverdisconnected', onDisconnected);
                    unlocking();
                }else{
                    alert("Can't unlock the page, not authenticated");
                }
            },
            error: function(){
                alert("error");
            }
        });
    }

    

    function unlocking() {
        exponentialBackoff(3 /* max retries */, 2 /* seconds delay */,
            function toTry() {
                time('Connecting to Bluetooth Device... ');
                return bluetoothDevice.gatt.connect();
            },
            function success() {
                console.log('> BLE connected. Page Unlocked');
                document.getElementById("secret").innerHTML = "UNLOCKED"; //unlock page
            },
            function fail() {
                time('Failed to reconnect.');
            });
    }

    function onDisconnected() {
        console.log('> BLE disconnected. Page Locked');
        document.getElementById("secret").innerHTML = "LOCKED"; //lock page
        checkIdBle(); //in check id ble there's connect() module, so if not same value it will be connected
    }

    function exponentialBackoff(max, delay, toTry, success, fail) {
        toTry().then(result => success(result))
        .catch(result => {
            if (max === 0) {
                return fail();
            }
            time('Retrying in ' + delay + 's... (' + max + ' tries left)');
            setTimeout(function() {
            exponentialBackoff(--max, delay * 2, toTry, success, fail);
            }, delay * 1000);
        });
    }

    function time(text) {
        var time = '[' + new Date().toJSON().substr(11, 8) + '] ' + text;
        console.log(time);
    }

    // hex converter
    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
</script>