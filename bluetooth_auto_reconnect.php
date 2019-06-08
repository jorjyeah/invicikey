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
    function append(text) {
        var para = document.createElement("P");
        var t = document.createTextNode(text);
        para.appendChild(t);
        document.getElementById("activity").appendChild(para);
    }

    $('#logout').click(function(e){
        sessionDisconnected();
    })
    $('#connect').click(function(e){
        navigator.bluetooth.requestDevice({acceptAllDevices: true})
        .then(device => {
            bluetoothDevice = device;
            bluetoothDevice.addEventListener('gattserverdisconnected', onDisconnected);
            document.getElementById("dev").innerHTML=device.name;
            connect();
        })
        .catch(error => { console.log(error); });
    });

    function connect() {
        exponentialBackoff(3 /* max retries */, 2 /* seconds delay */,
            function toTry() {
                time('Connecting to Bluetooth Device... ');
                return bluetoothDevice.gatt.connect();
            },
            function success() {
                append('connected');
            },
            function fail() {
                time('Failed to reconnect.');
            }
        );
    }

    function sessionDisconnected() {
        // if (!bluetoothDevice) {
        //     return;
        // }
        time("Disconnecting . . .");
        if (bluetoothDevice.gatt.connected) {
            bluetoothDevice.gatt.closed();
        } else {
            time("already disconnected");
        }
    }

    function onDisconnected(event) {
        append('disconnected');
        connect();
    }

    function exponentialBackoff(max, delay, toTry, success, fail) {
        toTry()
        .then(result => success(result))
        .catch(_ => {
            if (max == 0) {
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
        append(time);
    }
</script>