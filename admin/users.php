<?php
require_once '../config/config.php';

if(!is_logged_in() || !is_admin()){
    redirect(SITE_URL.'login.php');
}

$database = new Database();
$db = $database->getConnection();


// ADD USER

if(isset($_POST['add_user'])){

$username=trim($_POST['username']);
$email=trim($_POST['email']);
$password=$_POST['password'];
$full_name=trim($_POST['full_name']);
$role=$_POST['role'];
$phone=trim($_POST['phone']);
$address=trim($_POST['address']);
$status=$_POST['status'];

if(empty($username)
|| empty($email)
|| empty($password)
|| empty($full_name)){

die("Required fields missing");

}


/* check duplicate */

$check=$db->prepare(
"SELECT id FROM users
WHERE username=:username
OR email=:email"
);

$check->execute([

":username"=>$username,
":email"=>$email

]);

if($check->rowCount()>0){

die("Username or Email already exists");

}

$hashedPassword=password_hash(
$password,
PASSWORD_DEFAULT
);


$image="";

if(!empty($_FILES['profile_image']['name'])){

$target="../uploads/users/";

if(!file_exists($target)){
mkdir($target,0777,true);
}

$image=time()."_".$_FILES['profile_image']['name'];

move_uploaded_file(
$_FILES['profile_image']['tmp_name'],
$target.$image
);

}


$query="

INSERT INTO users(

username,
email,
password,
full_name,
role,
phone,
address,
profile_image,
status

)

VALUES(

:username,
:email,
:password,
:full_name,
:role,
:phone,
:address,
:profile_image,
:status

)

";


$stmt=$db->prepare($query);

$stmt->execute([

":username"=>$username,
":email"=>$email,
":password"=>$hashedPassword,
":full_name"=>$full_name,
":role"=>$role,
":phone"=>$phone,
":address"=>$address,
":profile_image"=>$image,
":status"=>$status

]);

header("Location:users.php");
exit;

}



// DELETE USER

if(isset($_GET['delete'])){

$id=$_GET['delete'];

$query="DELETE FROM users WHERE id=:id";

$stmt=$db->prepare($query);

$stmt->execute([
":id"=>$id
]);

header("Location:users.php");
exit;

}



// UPDATE USER

if(isset($_POST['update_user'])){

$id=$_POST['id'];

$image=$_POST['old_image'];

if(!empty($_FILES['profile_image']['name'])){

    $target="../uploads/users/";

    if(!file_exists($target)){
        mkdir($target,0777,true);
    }

    $image=time()."_".$_FILES['profile_image']['name'];

    move_uploaded_file(
        $_FILES['profile_image']['tmp_name'],
        $target.$image
    );
}


/* password update logic */

$passwordQuery="";

$params=[

":username"=>trim($_POST['username']),
":email"=>trim($_POST['email']),
":full_name"=>trim($_POST['full_name']),
":role"=>$_POST['role'],
":phone"=>trim($_POST['phone']),
":address"=>trim($_POST['address']),
":status"=>$_POST['status'],
":profile_image"=>$image,
":id"=>$id

];

if(!empty($_POST['password'])){

    $hashedPassword=password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );

    $passwordQuery=", password=:password";

    $params[':password']=$hashedPassword;

}


$query="

UPDATE users SET

username=:username,
email=:email,
full_name=:full_name,
role=:role,
phone=:phone,
address=:address,
status=:status,
profile_image=:profile_image

$passwordQuery

WHERE id=:id

";

$stmt=$db->prepare($query);

$stmt->execute($params);

header("Location: users.php");

exit;

}


$query="SELECT * FROM users ORDER BY id DESC";

$stmt=$db->prepare($query);

$stmt->execute();

$users=$stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html>
<head>

<title>User Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>

body{
background:#f5f7fb;
}

.card{

border:none;
border-radius:15px;
box-shadow:0 5px 20px rgba(0,0,0,.05);

}

.profile{

width:50px;
height:50px;
border-radius:50%;
object-fit:cover;
}

</style>

</head>

<body>  
    <?php include 'components/sidebar.php'; ?>

<div class="container mt-4" 
style="margin-left: auto;
    padding: 10px;
    width: fit-content; "
>

<div class="d-flex justify-content-between mb-3">

<h3>User Management</h3>

<button
class="btn btn-primary"
data-bs-toggle="modal"
data-bs-target="#addModal">

Add User

</button>

</div>


<div class="card p-3">

<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>Image</th>
<th>Name</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Phone</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach($users as $user): ?>

<tr>

<td>

<?php if($user['profile_image']){ ?>

<img
src="../uploads/users/<?php echo $user['profile_image']; ?>"
class="profile">

<?php }else{ ?>

<img
src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>"
class="profile">

<?php } ?>

</td>

<td>
<?php echo $user['full_name']; ?>
</td>

<td>
<?php echo $user['username']; ?>
</td>

<td>
<?php echo $user['email']; ?>
</td>

<td>

<span class="badge bg-primary">

<?php echo ucfirst($user['role']); ?>

</span>

</td>

<td>

<?php if($user['status']=="active"){ ?>

<span class="badge bg-success">

Active

</span>

<?php } else { ?>

<span class="badge bg-danger">

Inactive

</span>

<?php } ?>

</td>

<td>

<?php echo $user['phone']; ?>

</td>

<td>

<button
class="btn btn-warning btn-sm"
data-bs-toggle="modal"
data-bs-target="#edit<?php echo $user['id'];?>">

<i class="fa fa-edit"></i>

</button>


<a
href="?delete=<?php echo $user['id'];?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete user?')">

<i class="fa fa-trash"></i>

</a>

</td>

</tr>


<div class="modal fade" id="edit<?php echo $user['id'];?>">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST" enctype="multipart/form-data">

<input type="hidden"
name="id"
value="<?php echo $user['id'];?>">

<input type="hidden"
name="old_image"
value="<?php echo $user['profile_image'];?>">

<div class="modal-header">

<h5>Edit User</h5>

</div>

<div class="modal-body">

<input
name="full_name"
class="form-control mb-2"
value="<?php echo $user['full_name'];?>">

<input
name="username"
class="form-control mb-2"
value="<?php echo $user['username'];?>">

<input
name="email"
class="form-control mb-2"
value="<?php echo $user['email'];?>">

<input
type="password"
name="password"
class="form-control mb-2"
placeholder="Leave blank to keep existing password">

<input
name="phone"
class="form-control mb-2"
value="<?php echo $user['phone'];?>">

<textarea
name="address"
class="form-control mb-2"><?php echo $user['address'];?></textarea>

<select name="role" class="form-control mb-2">

<option value="admin">Admin</option>
<option value="teacher">Teacher</option>
<option value="parent">Parent</option>

</select>

<select name="status" class="form-control mb-2">

<option value="active">Active</option>
<option value="inactive">Inactive</option>

</select>

<input
type="file"
name="profile_image"
class="form-control">

</div>

<div class="modal-footer">

<button
name="update_user"
class="btn btn-success">

Update

</button>

</div>

</form>

</div>

</div>

</div>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>



<div class="modal fade" id="addModal">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST" enctype="multipart/form-data">

<div class="modal-header">

<h5>Add User</h5>

</div>

<div class="modal-body">

<input name="full_name" class="form-control mb-2" placeholder="Full Name" required>

<input name="username" class="form-control mb-2" placeholder="Username" required>

<input name="email" class="form-control mb-2" placeholder="Email" required>

<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>

<input name="phone" class="form-control mb-2" placeholder="Phone">

<textarea name="address" class="form-control mb-2" placeholder="Address"></textarea>

<select name="role" class="form-control mb-2">

<option value="admin">Admin</option>
<option value="teacher">Teacher</option>
<option value="parent">Parent</option>

</select>

<select name="status" class="form-control mb-2">

<option value="active">Active</option>
<option value="inactive">Inactive</option>

</select>

<input type="file"
name="profile_image"
class="form-control">

</div>

<div class="modal-footer">

<button
name="add_user"
class="btn btn-primary">

Save

</button>

</div>

</form>

</div>

</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>