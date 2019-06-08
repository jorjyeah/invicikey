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

    // check user, registered or not
    // if yes, will go to getKey() to get 
    function signIn(){
        <?php
            session_start();
            session_unset();
            session_destroy();
        ?>
        username = document.getElementById("username").value;
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'checkUsername','username':username},
            success: function(data){
                switch (data){
                    case 1: setTimeout(checkAuthenticated, 1000); getKey(); break;
                    case 0: alert("user not found"); break;
                }    
            },
            error: function(){
                alert("error");
            }
        });
    }

    function padHex(value) {
        return ('00' + value.toString(16).toUpperCase()).slice(-2);
    }
    
    var getKey = function(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'getKey','username':username},
            success: function(data){
                // alert("user found");
                console.log(data);
                auth_json = JSON.stringify(data);
                $('#sign_in_input').empty();
                $('#qrcode')
                .empty()
                .append("<p>Scan this qr code with invicikey apps</p>")
                .qrcode({width: 400,height: 400,text: auth_json});
                setTimeout(getKey, 30000);
            },
            error: function(){
                alert("error");
            }
        });
    }

    var checkAuthenticated = function(){
        $.ajax({
            type: 'post',
            url: "http://localhost/keyforce/auth_force.php",
            dataType: 'json',
            data:{'func':'checkAuthenticated','username':username},
            success: function(data){
                // setTimeout(checkAuthenticated, 1000);
                // console.log(data);
                if(!data){
                    setTimeout(checkAuthenticated, 1000);
                }else{
                    window.open("yourpage.php","_self");
                }
            },
            error: function(){
                alert("error");
            }
        });
    }
</script>