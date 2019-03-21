<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" media="all"/>
<script src="js/jquery-3.3.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <button type="button" class="btn btn-primary" id="connect">bluetooth</button>
        </div>
        <p id="dev"> device </p>
        <div class="row justify-content-center">
            <button type="button" class="btn btn-danger" id="logout">logout</button>
        </div>
        <div class="container" id ="activity">
        </div>
    </div>
</body>
</html>
<script>
    let unique = 0;
    $('#connect').click(function(e){
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
            }
            document.getElementById("dev").innerHTML=unique;
            unique = 0;
        });
    });

    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
</script>