
<?php
require_once 'config/config.php';

function get_general_settings($db){
    try{
        $stmt=$db->prepare("SELECT setting_key,setting_value FROM settings");
        $stmt->execute();
        $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings=[];
        foreach($rows as $r){
            $settings[$r['setting_key']]=$r['setting_value'];
        }

        return $settings;
    }catch(Exception $e){
        return [];
    }
}

function get_gallery($db){
    try{
        $stmt=$db->prepare("SELECT * FROM gallery WHERE is_active=1 ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch(Exception $e){
        return [];
    }
}

try{
    $database=new Database();
    $db=$database->getConnection();

    $general_settings=get_general_settings($db);
    $gallery_images=get_gallery($db);

    // Get CMS content for navbar and footer
    $cms_query = "SELECT * FROM homepage_cms WHERE is_active = 1 ORDER BY section, content_key";
    $cms_stmt = $db->prepare($cms_query);
    $cms_stmt->execute();
    $cms_results = $cms_stmt->fetchAll(PDO::FETCH_ASSOC);

    $cms_content = [];
    foreach ($cms_results as $row) {
        $cms_content[$row['section']][$row['content_key']] = $row;
    }

}catch(Exception $e){
    $gallery_images=[];
    $general_settings=[];
    $cms_content=[];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gallery</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
<style>
body{background:#f4f7fb}
.navbar{background:white;box-shadow:0 5px 20px rgba(0,0,0,.08)}
.gallery-page{padding:120px 0 80px}
.heading{text-align:center;margin-bottom:50px}
.heading h1{font-size:50px;font-weight:bold}
.heading p{color:#666}

.masonry{
columns:3;
column-gap:20px;
}
.card-item{
break-inside:avoid;
margin-bottom:20px;
overflow:hidden;
border-radius:20px;
position:relative;
background:white;
box-shadow:0 10px 25px rgba(0,0,0,.08);
cursor:pointer;
}
.card-item img{
width:100%;
transition:.5s;
}
.overlay{
position:absolute;
bottom:-100%;
left:0;
width:100%;
padding:20px;
background:linear-gradient(transparent,rgba(0,0,0,.9));
color:white;
transition:.5s;
}
.card-item:hover img{transform:scale(1.1)}
.card-item:hover .overlay{bottom:0}

.footer{
background:#111;
color:white;
padding:30px;
text-align:center;
}

.modal-img{
width:100%;
border-radius:15px;
}

@media(max-width:768px){
.masonry{columns:1}
.heading h1{font-size:35px}
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <?php 
      // Check if logo image exists in database
      $logo_image = $cms_content['header']['logo_image']['content_value'] ?? $cms_content['header']['logo_image']['image_path'] ?? '';
      if (!empty($logo_image)) {
          // Display image logo
          if (strpos($logo_image, 'http') === 0) {
              $logo_src = $logo_image;
          } else {
              $logo_src = 'uploads/homepage/' . $logo_image;
          }
          echo '<img src="' . htmlspecialchars($logo_src) . '" alt="Kidzenia Logo" style="height: 40px; margin-right: 10px;">';
      } else {
          // Display text logo
          echo htmlspecialchars($cms_content['header']['logo_text']['content_value'] ?? 'Kidzenia');
      }
      ?>
      <?php if (empty($logo_image)): ?>
        <span><?php echo htmlspecialchars($cms_content['header']['logo_text_span']['content_value'] ?? 'Kindergarten'); ?></span>
      <?php endif; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="./index#home"><?php echo htmlspecialchars($cms_content['nav']['link_home']['content_value'] ?? 'Home'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#programs"><?php echo htmlspecialchars($cms_content['nav']['link_programs']['content_value'] ?? 'Programs'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#about"><?php echo htmlspecialchars($cms_content['nav']['link_about']['content_value'] ?? 'About'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#gallery"><?php echo htmlspecialchars($cms_content['nav']['link_gallery']['content_value'] ?? 'Gallery'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#events"><?php echo htmlspecialchars($cms_content['nav']['link_events']['content_value'] ?? 'Events'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#contact"><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
      </ul>

      <a href="auth/login.php" class="btn btn-theme"><?php echo htmlspecialchars($cms_content['header']['admin_login_text']['content_value'] ?? 'Admin Login'); ?></a>
    </div>
  </div>
</nav>

<section class="gallery-page">
<div class="container">

<div class="heading">
<h1>Our Gallery</h1>
<p>Moments of learning, creativity and fun</p>
</div>

<div class="masonry">

<?php foreach($gallery_images as $img): 
$src='https://picsum.photos/500/700?random='.rand(1,999);

if(!empty($img['image_path'])){
if(strpos($img['image_path'],'http')===0){
$src=$img['image_path'];
}else{
$src='uploads/gallery/'.$img['image_path'];
}
}
?>

<div class="card-item"
onclick="showImage(
'<?php echo htmlspecialchars($src); ?>',
'<?php echo htmlspecialchars($img['title']??'Gallery'); ?>',
'<?php echo htmlspecialchars($img['description']??''); ?>'
)">

<img src="<?php echo $src; ?>">

<div class="overlay">
<h5><?php echo htmlspecialchars($img['title']??'Gallery'); ?></h5>
<p><?php echo htmlspecialchars($img['description']??''); ?></p>
</div>

</div>

<?php endforeach; ?>

</div>

</div>
</section>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <h4><i class="fas fa-graduation-cap me-2"></i>Kidzenia Kindergarten</h4>
                <p>Where learning begins with joy. We provide a nurturing environment for your child's early education and development.</p>
                <div class="mt-3">
                    <?php if (!empty($cms_content['footer']['facebook_url']['content_value'])): ?>
                        <a href="<?php echo htmlspecialchars($cms_content['footer']['facebook_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-facebook fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms_content['footer']['twitter_url']['content_value'])): ?>
                        <a href="<?php echo htmlspecialchars($cms_content['footer']['twitter_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-twitter fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms_content['footer']['instagram_url']['content_value'])): ?>
                        <a href="<?php echo htmlspecialchars($cms_content['footer']['instagram_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-instagram fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms_content['footer']['youtube_url']['content_value'])): ?>
                        <a href="<?php echo htmlspecialchars($cms_content['footer']['youtube_url']['content_value']); ?>" class="text-white" target="_blank"><i class="fab fa-youtube fa-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-3 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php#about" class="text-white-50">About Us</a></li>
                    <li class="mb-2"><a href="index.php#programs" class="text-white-50">Programs</a></li>
                    <li class="mb-2"><a href="index.php#gallery" class="text-white-50">Gallery</a></li>
                    <li class="mb-2"><a href="index.php#events" class="text-white-50">Events</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-white-50">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h5>Contact Info</h5>
                <p class="text-white-50">
                    <i class="fas fa-map-marker-alt me-2"></i><?php echo $general_settings['school_address'] ?? '123 Education Street, Learning City'; ?><br>
                    <i class="fas fa-phone me-2"></i><?php echo $general_settings['school_phone'] ?? '+1234567890'; ?><br>
                    <i class="fas fa-envelope me-2"></i><?php echo $general_settings['school_email'] ?? 'info@kidzenia.com'; ?>
                </p>
            </div>
            <div class="col-lg-3 mb-4">
                <h5>Location</h5>
                <?php if (!empty($cms_content['contact']['map_url']['content_value'])): ?>
                <div class="map-container-footer" style="height: 200px; border-radius: 8px; overflow: hidden;">
                    <iframe src="<?php echo htmlspecialchars($cms_content['contact']['map_url']['content_value']); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <?php else: ?>
                <p class="text-white-50">Map not configured. Please set the map URL in admin settings.</p>
                <?php endif; ?>
            </div>
        </div>
        <hr class="bg-secondary">
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Kidzenia Kindergarten. All rights reserved.</p>
        </div>
    </div>
</footer>

<div class="modal fade" id="galleryModal">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content">

<div class="modal-body text-center">

<div class="mb-3">

<button class="btn btn-dark me-2"
onclick="prevImage()">
<i class="fas fa-chevron-left"></i>
</button>

<button class="btn btn-dark me-2"
onclick="nextImage()">
<i class="fas fa-chevron-right"></i>
</button>

<button class="btn btn-primary me-2"
onclick="zoomIn()">
<i class="fas fa-search-plus"></i>
</button>

<button class="btn btn-primary"
onclick="zoomOut()">
<i class="fas fa-search-minus"></i>
</button>

</div>

<div style="overflow:hidden">

<img
id="modalImg"
class="modal-img"
style="
max-height:75vh;
transition:.3s;
cursor:grab;
"
>

</div>

<h4 id="modalTitle" class="mt-3"></h4>

<p id="modalDesc"></p>

</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

let galleryData=[];
let currentIndex=0;
let currentScale=1;
let modal=null;

document.addEventListener(
"DOMContentLoaded",
()=>{

galleryData=[

<?php foreach($gallery_images as $img):

$src='https://picsum.photos/500/700?random='.rand(1,999);

if(!empty($img['image_path'])){
if(strpos($img['image_path'],'http')===0){
$src=$img['image_path'];
}else{
$src='uploads/gallery/'.$img['image_path'];
}
}

?>

{
img:'<?php echo htmlspecialchars($src); ?>',
title:'<?php echo htmlspecialchars($img['title']??'Gallery'); ?>',
desc:'<?php echo htmlspecialchars($img['description']??''); ?>'
},

<?php endforeach; ?>

];

modal=new bootstrap.Modal(
document.getElementById("galleryModal")
);

});

function showImage(img,title,desc){

currentIndex=galleryData.findIndex(
x=>x.img===img
);

if(currentIndex<0){
currentIndex=0;
}

updateModal();

modal.show();

}

function updateModal(){

let item=galleryData[currentIndex];

document.getElementById(
"modalImg"
).src=item.img;

document.getElementById(
"modalTitle"
).innerText=item.title;

document.getElementById(
"modalDesc"
).innerText=item.desc;

currentScale=1;

document.getElementById(
"modalImg"
).style.transform="scale(1)";

}

function nextImage(){

currentIndex++;

if(currentIndex>=galleryData.length){
currentIndex=0;
}

updateModal();

}

function prevImage(){

currentIndex--;

if(currentIndex<0){
currentIndex=
galleryData.length-1;
}

updateModal();

}

function zoomIn(){

currentScale+=0.2;

document.getElementById(
"modalImg"
).style.transform=
`scale(${currentScale})`;

}

function zoomOut(){

if(currentScale>1){

currentScale-=0.2;

document.getElementById(
"modalImg"
).style.transform=
`scale(${currentScale})`;

}

}

document.addEventListener(
"keydown",
function(e){

if(
document.getElementById(
"galleryModal"
).classList.contains("show")
){

if(e.key==="ArrowRight"){
nextImage();
}

if(e.key==="ArrowLeft"){
prevImage();
}

if(
e.key==="+" ||
e.key==="="
){
zoomIn();
}

if(e.key==="-"){
zoomOut();
}

}

});

</script>

</body>
</html>
