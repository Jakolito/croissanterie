<?php 
include ('connect.php');
session_start();
$default_navigator_color = "#A59D84";
$default_body_color = "#ECEBDE";
$default_button_color = "#6f4e37";
$default_logo_image = "img/logo.jpg";
$default_slide_image = "img/img1.png";
$default_slide2_image = "img/img2.png";
$default_slide3_image = "img/img3.png";

$query = "SELECT * FROM theme_cafe WHERE id = 1";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
  $row = mysqli_fetch_assoc($result);
  $current_navigator_color = $row['navigator'];
  $current_body_color = $row['body'];
  $current_font_color = $row['font'];
  $current_button_color = $row['button'];
  $current_logo_image = $row['logo'];
  $current_slide_image =  $row['slide'];;
  $current_slide2_image =  $row['slide2'];;
  $current_slide3_image =  $row['slide3'];;
} else {
  $current_navigator_color = $default_navigator_color;
  $current_body_color = $default_body_color;
  $current_font_color = $default_font_color;
  $current_button_color = $default_button_color;
  $current_logo_image = $default_logo_image ;
  $current_slide_image = $default_slide_image;
  $current_slide2_image = $default_slide2_image;
  $current_slide3_image = $default_slide3_image;
}

$offers_query = "SELECT img, description FROM offers ORDER BY id DESC";
$offers_result = mysqli_query($conn, $offers_query);
$offers = [];
if (mysqli_num_rows($offers_result) > 0) {
    while ($offer = mysqli_fetch_assoc($offers_result)) {
        $offers[] = $offer;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silver Line - Forgot Account</title>
	<link rel="stylesheet" href="style.css">
    <!-- Font Awesome CDN for the search icon -->
	<link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
          body {
	
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
			background-color:<?php echo$current_body_color ?>;
        }
			 .a1, .a2{
      background-color: <?php echo$current_button_color ?>;
    }
.divheader {
	position: fixed;
	top: 0;
	width: 100%;
	background-color: <?php echo$current_navigator_color?>;
	border: 1px solid #A59D84;
	  z-index: 2000;
}

        .div_forgot {
            background-color: #fff;
            width: 400px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(10px);
        }

        h2 {
            text-align: center;
            font-size: 28px;
            color: #A59D84;
            margin-bottom: 20px;
        }

        label {
            font-size: 23px;
            color: #A59D84;
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }

        .input-container {
            position: relative;
            width: 100%;
        }

        input[type="email"] {
            width: 328PX;
            padding: 12px 35px ;
            border-radius: 10px;
            border: 1px solid #A59D84;
            margin-bottom: 15px;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #595037;
            transition: 0.3s ease;
        }

        input[type="email"]:focus {
            border-color: #4e3629;
            box-shadow: 0 0 5px #4e3629;
            background-color: rgba(255, 255, 255, 1);
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 22PX;
            transform: translateY(-50%);
            color: #6f4e37;
            font-size: 18px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background-color: #6f4e37;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #4e3629;
            /* transform: scale(1.05); */
        }

        .cancel-btn {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }

        .cancel-btn a {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            background-color: #444;
            width: 100%;
            color: #6f4e37;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .cancel-btn a:hover {
            background-color: white;
            /* transform: scale(1.05); */
        }

        
        .navigator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background-color: #6f4e37;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 509;
        }

        .navigator .nav-item {
            color: #fff;
            position: relative;
            left: -600px;
            font-size: 18px;
            font-weight: bold;
            margin: 0 40px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .navigator .nav-item:hover {
            color: #d3ad7f;
        }
			.logo{
		height:75px;
		border: none;
		border-radius: 50%;
		position: absolute;
		left: 0;
		top:0;
			
	}
    </style>
</head>

<body>
    <!-- <div class="logo">
        <img src="cafe/kopii-removebg-preview.png" alt="Coffee District Logo" width="200px">
    </div> -->
    <div class="divheader">
	
		<nav>
		
			<ul>
					<img class ="logo" src="<?php echo$current_logo_image ?>">
				<li>" "</li>
					<li><a href="home.html">Home</a></li>
				<li>
					<a href="#">Cards▾</a>
				
				</li>
				<li><a href="Aboutus.html">About Us</a></li>
				<li><a href="Contactus.html">Contact Us</a></li>
			</ul>
		</nav>
		<a class="a1" href="login.php">Log in <i class='bx bx-user'></i></a>
		<a class="a2" href="register.php">Register</a>
	</div>

    <div class="div_forgot">
        <h2>Find Your Account</h2>
        <form method="post" name="form1" action="forgot_email_otp.php">
            <label for="txtemail">Enter your email address to search for your account.</label>
            
            <div class="input-container">
                <i class="fas fa-search search-icon"></i> <!-- Search icon -->
                <input type="email" id="txtemail" name="txtemail" placeholder="Enter your email" required>
            </div>

            <input type="submit" value="Search">
        </form>

        <div class="cancel-btn">
            <a href="log.php">Cancel</a>
        </div>
    </div>
</body>

</html>
