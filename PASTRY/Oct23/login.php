

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: <?php echo$current_body_color ?>;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
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
    }
    .loginBtn{
        background-color:  <?php echo$current_button_color ?>;
		height: 35px;
		margin-left: -25px;
    }
    h2{
        font-size: 30px;
    } 
	.container-login{
		 background-color: #fff;
            width: 400px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
			
	}
	 input[type="password"] {
            width: 330px;
            padding: 12px 35px;
            border-radius: 10px;
            border: 1px solid #A59D84;
            margin-bottom: 15px;
			margin-left: -2px;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #595037;
            transition: 0.3s ease;
        }
		 input[type="text"] {
            width: 330px;
            padding: 12px 35px;
            border-radius: 10px;
            border: 1px solid #A59D84;
            margin-bottom: 15px;
			margin-left: -2px;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #595037;
            transition: 0.3s ease;
        }

        input[type="password"]:focus {
            border-color: #4e3629;
            box-shadow: 0 0 5px #4e3629;
            background-color: rgba(255, 255, 255, 1);
        }
		.la{
		position:relative;
			bottom:5px;
		left: 45px;
			font-size: 12px;
	}
	
	.la1{
		position:relative;
		bottom:30px;
		left:0px;
		font-size: 12px;
		
nav ul {
    display: flex; /* Use flexbox for the list */
    list-style: none; /* Remove bullet points */
    margin: 0; /* Remove default margin */
    padding: 0; /* Remove default padding */
    justify-content: center; /* Center the links horizontally */
    align-items: center; /* Center the links vertically */
    flex-grow: 1; /* Allow the list to grow and take available space */
}

nav ul li {
    margin: 0 15px; /* Add some space between links */
}

nav ul li a {
    text-decoration: none; /* Remove underline from links */
    color: #fff; /* Change link color */
    font-weight: bold; /* Make links bold */
    font-size: 16px; /* Adjust the font size if needed */
}
body {
	font-family: Arial, sans-serif;
	margin: 0;
	padding: 0;
	background-color: <?php echo$current_body_color ?>;
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100vh;

}
 .a1, .a2{
      background-color: <?php echo$current_button_color ?>;
    }

.pagimg{
	
		padding: 10px; 
		margin-top:50px;
		margin-left:50px;
		margin-right:74px;
		width: 250px;
	height: 250px;
	
	}
	
	
	.pag1{
		text-align:center;
	}
	.logo{
		height:75px;
		border: none;
		border-radius: 50%;
		position: absolute;
		left: 40px;;
		top:0;
		
		
	}
	
    .pag {	
		position: absolute;
		left: 70px;
        display: flex; /* Enable flexbox */
        flex-direction: column; /* Stack items vertically */
        align-items: center; /* Center items horizontally */
        justify-content: center; /* Center items vertically */
        text-align: center; /* Center text inside the div */
        margin: 0 auto; /* Center the div within its container */
        width: 90%; /* Optional: Adjust width as needed */
    }

    .offers-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 20px;
        margin: 20px auto;
        max-width: 1200px;
    }
    .offer-card {
        background-color: <?php echo $current_navigator_color ?>;
        color: #fff;
        border-radius: 10px;
        width: 300px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-align: center;
        overflow: hidden;
    }
    .offer-card img {
        margin-top: 10px;
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 5px;
    }
    .offer-card .description {
        padding: 15px;
        font-size: 16px;
    }


.text-content {
    position: absolute;
    top: -100px;
    left: 130px;
    color: white;
	width: 400px;

	
}
.text-content h2 {
    margin: 0;
	font-size: 35px;
}
.text-content p {
    margin: 5px 0;
	font-size: 20px;
}
nav {
    display: flex;
    justify-content: space-between; /* Space between logo and links */
    align-items: center; /* Center items vertically */
    padding: 10px 20px; /* Add some padding */
}

nav ul {
    display: flex; /* Use flexbox for the list */
    list-style: none; /* Remove bullet points */
    margin: 5px 0; /* Remove default margin */
	
    padding: 0; /* Remove default padding */
    flex-grow: 1; /* Allow the list to grow and take available space */
    justify-content: center; /* Center the links */
}

nav ul li {
    margin: 0 15px; /* Add some space between links */
}

nav ul li a {
    text-decoration: none; /* Remove underline from links */
    color: #fff; /* Change link color */
    font-weight: bold; /* Make links bold */
} 
.aboutus {
    position: absolute; /* Position relative for absolute positioning of text */
	top: 1700px;
	left: 120px;
	height: 500px;
	width: 1100px;

}
.aboutus img{
	border-radius: 10px;
}

.abouttxt{
	position: absolute;
	border-radius: 10px;
	padding: 5px 30px;
	width: 500px;
	top: 130px;
	left: 40px;
	background-color: white;
	z-index: 200px;
	
}
.slideshow-container{
	po
	border: 1px solid black;
} 
.divheader {
	position: fixed;
	top: 0;
	width: 100%;
	height: 100px !important;
	background-color: <?php echo$current_navigator_color?>;
	border: 1px solid #A59D84;
	  z-index: 2000;
}
    </style>
</head>
<body>
   <div class="divheader">
    <nav>
        <img class="logo" src="<?php echo $current_logo_image ?>">
        <ul>
            <li><a href="aso.php">Home</a></li>
            <li><a href="#offers">Offers▾</a></li>
            <li><a href="#aboutus">About Us</a></li>
           <li><a href="#contactus">Contact Us</a></li>
        </ul>
        <div>
            <a class="a1" href="login.php">Log in <i class='bx bx-user'></i></a>
            <a class="a2" href="register.php">Register</a>
        </div>
    </nav>
</div>


    <div class="container-login">
        <h2>Log in </h2>
        <img class ="logo" src="<?php echo$current_logo_image ?>">
        <form action="login.php" method="POST">
            <input class="log" type="text" name="username" id="username"  placeholder="Enter Username" required> 
            <br>
            <input class="log"  type="password" name="password" id="password"  placeholder=" Enter Password"  required > </input>
            <br>
            <input class="loginBtn" type="submit" value="Log in" name="loginBtn" id="loginBtn">
            <label class="la">Don't have an account? </label> 
            <a class="la" href="register.php">Sign Up</a><br>
            <label class="la1">Forgotten Password?</label>  
            <a class="la1" href="forgotemail.php">Forgot</a>
        </form>
    </div>
</body>
</html>
<?php
include('connect.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the username and password from POST
    $username = mysqli_real_escape_string($conn, htmlspecialchars($_POST['username']));
    $password = $_POST['password'];

    // Ensure inputs are not empty
    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all fields.'); window.location.href = 'login.php';</script>";
        exit;
    }

    // Check if the user is an admin
    $adminQuery = "SELECT * FROM admin WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $adminQuery);
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $adminResult = mysqli_stmt_get_result($stmt);

    if ($adminResult && mysqli_num_rows($adminResult) > 0) {
        $admin = mysqli_fetch_assoc($adminResult);

        // Verify password (handle both hashed and plain text passwords)
        if (password_verify($password, $admin['passwords']) || $password === $admin['passwords']) {
            // Set admin session
            $_SESSION['admin'] = $admin['email']; // Store email in session
            $_SESSION['admin_username'] = $admin['username'];

            echo "<script>alert('Welcome, Admin!'); window.location.href = 'admindash.php';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid admin username or password.'); window.location.href = 'login.php';</script>";
            exit;
        }
    }

    // Check if the user is a regular account user
    $userQuery = "SELECT * FROM account WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);

    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $user = mysqli_fetch_assoc($userResult);

        // Check if the account is blocked
        if ($user['status'] === 'Blocked') {
            echo "<script>alert('Your account has been blocked. Please contact support.'); window.location.href = 'login.php';</script>";
            exit();
        }

        // Verify password (handle both hashed and plain text passwords)
        if (password_verify($password, $user['passwords']) || $password === $user['passwords']) {
            // Reset failed attempts on successful login
            $stmt = $conn->prepare("UPDATE account SET failed_attempts = 0 WHERE AccountID = ?");
            $stmt->bind_param("i", $user['AccountID']);
            $stmt->execute();
            $stmt->close();

            // Save user details in session
            $_SESSION['user'] = $user['AccountID'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['account_type'] = $user['account_type'];

            echo "<script>alert('Login successful! Welcome back.'); window.location.href = 'homepage.php';</script>";
            exit;
        } else {
            // Increment failed attempts
            $stmt = $conn->prepare("UPDATE account SET failed_attempts = failed_attempts + 1 WHERE AccountID = ?");
            $stmt->bind_param("i", $user['AccountID']);
            $stmt->execute();
            $stmt->close();

            // Check if the user should be blocked
            if ($user['failed_attempts'] + 1 >= 3) {
                $stmt = $conn->prepare("UPDATE account SET status = 'Blocked' WHERE AccountID = ?");
                $stmt->bind_param("i", $user['AccountID']);
                $stmt->execute();
                $stmt->close();
                echo "<script>alert('Your account has been blocked due to too many failed login attempts.'); window.location.href = 'login.php';</script>";
            } else {
                echo "<script>alert('Invalid username or password.'); window.location.href = 'login.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = 'login.php';</script>";
    }

    mysqli_stmt_close($stmt);
}

// Close the database connection
mysqli_close($conn);
?>



