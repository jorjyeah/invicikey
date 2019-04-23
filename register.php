<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" media="all"/>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/jquery.qrcode.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <!-- <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <title>Register - Invicikey</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1 class="text-center">Register Invicikey</h1>
    <hr>
    <div class="container" id="register_input">
        <!-- <form action="register.php" id="register_form"> -->
            <div class="form-group">
                <label for="username">Username:</label>
                <input class="form-control" id="username">
            </div>
            <button type="submit" class="btn btn-primary" id="register" onclick="register()">Register</button>
        <!-- </form> -->
        <hr>
    </div>
    <div class="d-flex justify-content-center">
        <div class="container" id="qrcode">
        </div>
    </div>
</body>
</html>
<script>
    var username;
    var challenge;
    var updateInterval;

    // function for check user availability
    function register(){
        username = document.getElementById("username").value;
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/reg_force.php",
            dataType: 'json',
            data:{'func':'checkUsername','username':username},
            success: function(data){
                switch (data){
                    case 1: generateQR(); break;
                    case 0: alert("username have already taken, please type new name"); break;
                }    
            },
            error: function(){
                alert("error");
            }
        });
    }

    // get detail data ==> new challenge
    // trigger function make qr
    function getDetailData(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/reg_force.php",
            dataType: 'json',
            data:{'func':'generateNewChallenge',},
            success: function(data){
                challenge = data;
                templateQR();
            },
            error: function(){
                alert("error");
            }
        });
    }
    
    // check user has been registered or not, check keyhandle
    // if not registered do nothing
    // if registered, will stop checking and move to ble registration
    function checkRegistered(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/reg_force.php",
            dataType: 'json',
            data:{'func':'checkKey','username':username},
            success: function(data){
                switch (data){
                    case 1: break;
                    case 0: clearInterval(updateInterval); registerBluetooth(); break;
                }   
            },
            error: function(){
                alert("error");
            }
        });
    }

    // javascript for creating qr
    function templateQR(){
        //OBJECT
        reg_data = {"action":"registration", "username":username, "appId":"http://192.168.100.64/invicikey", "challenge":challenge,"reg_portal":"http://192.168.100.64/keyforce/"};
        //JSON
        reg_json = JSON.stringify(reg_data);
        $('#register_input').empty();
        $('#qrcode')
        .empty()
        .append("<p>Scan this qr code with invicikey apps</p>")
        .qrcode({width: 256,height: 256,text: reg_json});
        console.log(reg_data);
    }

    // function for generate qr first time
    function generateQR(){
        getDetailData();
        updateInterval = setInterval(checkRegistered,5000);
    }

    // input unique id to server
    function inputUnique(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/reg_force.php",
            dataType: 'json',
            data:{'func':'inputUnique','username':username,'unique':unique},
            success: function(data){
                switch (data){
                    case 1: window.open("sign_in.php","_self"); break;
                    //case 0: alert("Failed, Try Again."); deleteUser(); break;
                }    
            },
            error: function(){
                alert("error");
            }
        });
    }

    // javascript for activate bluetooth button
    function registerBluetooth(){
        $('#qrcode')
        .empty()
        .append("<p>Press this button to register your BLE</p>")
        .append("<button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>");
    }

    let unique = 0;
    // function web bluetooth api to get unique id
    function pairing(){
        navigator.bluetooth.requestDevice({
            acceptAllDevices:true,
            optionalServices: ['device_information']
        })
        .then(device => {
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
                        });
                    break;
                    default: console.log('> Unknown Characteristic: ' + characteristic.uuid);
                }
            });
            return queue;
        })
        .catch(error => {
            console.log('Argh! ' + error);
        }).
        then(() => {
            console.log(unique);
            if(unique == 0){
                alert("retry");
            }else{
                alert("success");
                inputUnique();
            }
            unique = 0;
        });
    }

    // hex converter
    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
</script>