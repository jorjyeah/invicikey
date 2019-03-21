<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" media="all"/>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/jquery.qrcode.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <!-- <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <title>Sign In - Invicikey</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1 class="text-center">Invicikey</h1>
    <hr>
    <div class="container" id="sign_in_input">
        <div class="form-group">
            <label for="username">Username:</label>
            <input class="form-control" id="username">
        </div>
        <button type="submit" class="btn btn-primary" id="signin" onclick="signIn()">Sign In</button>
        <hr>
        <p> First time user please <a href="register.php">Register</a></p>
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

    function signIn(){
        username = document.getElementById("username").value;
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'checkUsername','username':username},
            success: function(data){
                switch (data){
                    case 1: alert("user found"); getKey(); break;
                    case 0: alert("user not found"); break;
                }    
            },
            error: function(){
                alert("error");
            }
        });
    }

    function getKey(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'getKey','username':username},
            success: function(data){
                generateQR(data);
            },
            error: function(){
                alert("error");
            }
        });
    }
    

    function generateQR(key){
        console.log(key);
        // updateInterval = setInterval(updateQR,5000);
    }

    function updateQR(){
        checkRegistered()
    }

    // function getChallengeKey(){
    //     $.ajax({
    //         type: 'post',
    //         url: "http://localhost/keyforce/auth_force.php",
    //         dataType: 'json',
    //         data:{'func':'getChallengeKey'},
    //         success: function(data){
    //             challenge = data;
    //             templateQR();
    //         },
    //         error: function(){
    //             alert("error");
    //         }
    //     });
    // }
    
    // function checkRegistered(){
    //     $.ajax({
    //         type: 'post',
    //         url: "http://localhost/keyforce/reg_force.php",
    //         dataType: 'json',
    //         data:{'func':'checkUsername','username':username},
    //         success: function(data){
    //             switch (data){
    //                 case 1: getDetailData(); break;
    //                 case 0: clearInterval(updateInterval); registerBluetooth(); break;
    //             }   
    //         },
    //         error: function(){
    //             alert("error");
    //         }
    //     });
    // }

    // function templateQR(){
    //     //OBJECT
    //     reg_data = {"action":"registration", "username":username, "appId":"https://jorjyeah.xyz", "challenge":challenge,"auth_portal":"https://jorjyeah.xyz/keyforce/register-portal.php"};
    //     //JSON
    //     reg_json = JSON.stringify(reg_data);
    //     $('#register_input').empty();
    //     $('#qrcode')
    //     .empty()
    //     .append("<p>Scan this qr code with invicikey apps</p>")
    //     .qrcode({width: 256,height: 256,text: reg_json})
    //     .append("<p>Press this button to register your BLE</p>")
    //     .append("<button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>");
    //     console.log(reg_data);
    // }

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

    function registerBluetooth(){
        $('#qrcode')
        .empty()
        .append("<p>Press this button to register your BLE</p>")
        .append("<button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>");
    }
    let unique = 0;
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

    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
</script>