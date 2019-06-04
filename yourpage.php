<?php
echo "welcome"
?>
<script>
    // function inputUnique(){
    //     $.ajax({
    //         type: 'post',
    //         url: "http://localhost/keyforce/reg_force.php",
    //         dataType: 'json',
    //         data:{'func':'inputUnique','username':username,'unique':unique},
    //         success: function(data){
    //             switch (data){
    //                 case 1: window.open("sign_in.php","_self"); break;
    //                 //case 0: alert("Failed, Try Again."); deleteUser(); break;
    //             }    
    //         },
    //         error: function(){
    //             alert("error");
    //         }
    //     });
    // }

    // function registerBluetooth(){
    //     $('#qrcode')
    //     .empty()
    //     .append("<p>Press this button to register your BLE</p>")
    //     .append("<button type='submit' class='btn btn-primary' id='pair' onclick='pairing()'>Pair</button>");
    // }
    // let unique = 0;
    // function pairing(){
    //     navigator.bluetooth.requestDevice({
    //         acceptAllDevices:true,
    //         optionalServices: ['device_information']
    //     })
    //     .then(device => {
    //         console.log("connecting...");        
    //         return device.gatt.connect();
    //     })
    //     .then(server => {
    //         console.log("getting service...");
    //         return server.getPrimaryService('device_information');
    //     })
    //     .then(service => {
    //         console.log("getting characteristic...");
    //         if('system_id'){
    //             return service.getCharacteristics('system_id');
    //         }
    //         return service.getCharacteristics();
    //     })
    //     .then(characteristics => {
    //         let queue = Promise.resolve();
    //         let decoder = new TextDecoder('utf-8');
    //         characteristics.forEach(characteristic => {
    //             switch (characteristic.uuid) {
    //                 case BluetoothUUID.getCharacteristic('system_id'):
    //                     queue = queue.then(_ => characteristic.readValue()).then(value => {
    //                         unique = padHex(value.getUint8(7)) + padHex(value.getUint8(6)) + padHex(value.getUint8(5));
    //                         console.log(unique);
    //                     });
    //                 break;
    //                 default: console.log('> Unknown Characteristic: ' + characteristic.uuid);
    //             }
    //         });
    //         return queue;
    //     })
    //     .catch(error => {
    //         console.log('Argh! ' + error);
    //     }).
    //     then(() => {
    //         console.log(unique);
    //         if(unique == 0){
    //             alert("retry");
    //         }else{
    //             alert("success");
    //             inputUnique();
    //         }
    //         unique = 0;
    //     });
    // }
</script>