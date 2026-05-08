<?php
require_once '../config/config.php';

if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

/*
|--------------------------------------------------------------------------
| CREATE DEFAULT CMS ITEMS
|--------------------------------------------------------------------------
*/

$defaultItems = [

[
'section'=>'header',
'content_key'=>'logo',
'content_type'=>'image'
],

[
'section'=>'hero',
'content_key'=>'hero_title',
'content_type'=>'text'
],

[
'section'=>'hero',
'content_key'=>'hero_subtitle',
'content_type'=>'textarea'
],

[
'section'=>'hero',
'content_key'=>'hero_image',
'content_type'=>'image'
],

[
'section'=>'cta',
'content_key'=>'cta_title',
'content_type'=>'text'
],

[
'section'=>'cta',
'content_key'=>'cta_description',
'content_type'=>'textarea'
],

[
'section'=>'footer',
'content_key'=>'footer_description',
'content_type'=>'textarea'
]

];

foreach($defaultItems as $default){

$check = $db->prepare("
SELECT id
FROM homepage_cms
WHERE section = ?
AND content_key = ?
");

$check->execute([
$default['section'],
$default['content_key']
]);

if(!$check->fetch()){

$insert = $db->prepare("
INSERT INTO homepage_cms
(
section,
content_key,
content_type,
content_value,
is_active,
created_at
)
VALUES
(
?,
?,
?,
'',
1,
NOW()
)
");

$insert->execute([
$default['section'],
$default['content_key'],
$default['content_type']
]);

}

}

/*
|--------------------------------------------------------------------------
| AJAX CRUD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {

    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    try {

        /*
        |--------------------------------------------------------------------------
        | UPDATE CONTENT
        |--------------------------------------------------------------------------
        */

        if ($action == 'update_content') {

            $id = $_POST['id'];
            $value = $_POST['value'];

            $query = "
                UPDATE homepage_cms
                SET
                content_value = :value,
                updated_at = NOW()
                WHERE id = :id
            ";

            $stmt = $db->prepare($query);

            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':id', $id);

            $stmt->execute();

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | ADD ITEM
        |--------------------------------------------------------------------------
        */

        if ($action == 'add_item') {

            $section = $_POST['section'];
            $content_key = $_POST['content_key'];
            $content_type = $_POST['content_type'];

            $query = "
                INSERT INTO homepage_cms
                (
                    section,
                    content_key,
                    content_type,
                    content_value,
                    is_active,
                    created_at
                )
                VALUES
                (
                    :section,
                    :content_key,
                    :content_type,
                    '',
                    1,
                    NOW()
                )
            ";

            $stmt = $db->prepare($query);

            $stmt->bindParam(':section', $section);
            $stmt->bindParam(':content_key', $content_key);
            $stmt->bindParam(':content_type', $content_type);

            $stmt->execute();

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE ITEM
        |--------------------------------------------------------------------------
        */

        if ($action == 'delete_item') {

            $id = $_POST['id'];

            $query = "
                DELETE FROM homepage_cms
                WHERE id = :id
            ";

            $stmt = $db->prepare($query);

            $stmt->bindParam(':id', $id);

            $stmt->execute();

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        if ($action == 'upload_image') {

            $id = $_POST['id'];

            if (
                isset($_FILES['file']) &&
                $_FILES['file']['error'] == 0
            ) {

                if (!is_dir('../uploads/homepage')) {
                    mkdir('../uploads/homepage', 0755, true);
                }

                $file = $_FILES['file'];

                $filename =
                    time() .
                    '_' .
                    basename($file['name']);

                $target =
                    '../uploads/homepage/' .
                    $filename;

                move_uploaded_file(
                    $file['tmp_name'],
                    $target
                );

                $query = "
                    UPDATE homepage_cms
                    SET
                    image_path = :img,
                    content_value = :img,
                    updated_at = NOW()
                    WHERE id = :id
                ";

                $stmt = $db->prepare($query);

                $stmt->bindParam(':img', $filename);
                $stmt->bindParam(':id', $id);

                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'image' => $filename
                ]);

                exit;
            }

            echo json_encode([
                'success' => false
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | ADD CARD
        |--------------------------------------------------------------------------
        */

        if ($action == 'add_card') {

            $section = $_POST['section'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $icon_type = $_POST['icon_type'] ?? 'fa';
            $icon_value = $_POST['icon_value'] ?? '';
            $badge = $_POST['badge'] ?? '';

            $stmt = $db->prepare("
                SELECT COALESCE(MAX(sort_order),0)+1 as nextOrder
                FROM dynamic_cards
                WHERE section = ?
            ");

            $stmt->execute([$section]);

            $next = $stmt->fetch(PDO::FETCH_ASSOC);

            $order = $next['nextOrder'];

            $insert = $db->prepare("
                INSERT INTO dynamic_cards
                (
                    section,
                    title,
                    description,
                    icon_type,
                    icon_value,
                    badge,
                    sort_order
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $insert->execute([
                $section,
                $title,
                $description,
                $icon_type,
                $icon_value,
                $badge,
                $order
            ]);

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE CARD
        |--------------------------------------------------------------------------
        */

        if ($action == 'update_card') {

            $id = $_POST['id'];
            $field = $_POST['field'];
            $value = $_POST['value'];

            $allowed = ['title', 'description', 'icon_type', 'icon_value', 'badge'];

            if (!in_array($field, $allowed)) {
                echo json_encode(['success' => false, 'error' => 'Invalid field']);
                exit;
            }

            $query = "
                UPDATE dynamic_cards
                SET {$field} = :value,
                updated_at = NOW()
                WHERE id = :id
            ";

            $stmt = $db->prepare($query);

            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':id', $id);

            $stmt->execute();

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE CARD
        |--------------------------------------------------------------------------
        */

        if ($action == 'delete_card') {

            $id = $_POST['id'];

            $stmt = $db->prepare("
                UPDATE dynamic_cards
                SET is_active = 0
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            echo json_encode([
                'success' => true
            ]);

            exit;
        }

    } catch (PDOException $e) {

        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET CARDS
|--------------------------------------------------------------------------
*/

function getCards($db, $section)
{
    $stmt = $db->prepare("
        SELECT *
        FROM dynamic_cards
        WHERE section = ?
        AND is_active = 1
        ORDER BY sort_order ASC
    ");

    $stmt->execute([$section]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| FETCH CMS
|--------------------------------------------------------------------------
*/

$query = "
    SELECT *
    FROM homepage_cms
    WHERE is_active = 1
    ORDER BY section ASC, id ASC
";

$stmt = $db->prepare($query);

$stmt->execute();

$cms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];

foreach ($cms as $item) {

    $grouped[$item['section']][] = $item;

}

$features = getCards($db, 'features');

$programs = getCards($db, 'programs');

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Homepage Visual CMS</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
rel="stylesheet"
>

<style>

:root{
--primary:#7C3AED;
--secondary:#EC4899;
--bg:#F8FAFC;
--dark:#0F172A;
--border:#E2E8F0;
}

body{
font-family:'Inter',sans-serif;
background:var(--bg);
margin:0;
}

.sidebar{
position:fixed;
top:0;
left:0;
width:280px;
height:100vh;
background:linear-gradient(180deg,#7C3AED,#5B21B6);
padding:30px 20px;
overflow:auto;
z-index:1000;
}

.sidebar h2{
color:white;
font-weight:700;
margin-bottom:30px;
}

.sidebar a{
display:flex;
align-items:center;
gap:12px;
padding:14px 16px;
color:white;
text-decoration:none;
border-radius:12px;
margin-bottom:10px;
transition:0.3s;
}

.sidebar a:hover{
background:rgba(255,255,255,0.15);
}

.main{
margin-left:280px;
padding:40px;
}

.topbar{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.btn-main{
background:linear-gradient(135deg,var(--primary),var(--secondary));
border:none;
padding:12px 24px;
border-radius:14px;
color:white;
font-weight:600;
text-decoration:none;
}

.section-card{
background:white;
border-radius:24px;
padding:30px;
margin-bottom:30px;
box-shadow:0 10px 30px rgba(0,0,0,0.05);
}

.section-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
}

.section-title{
font-size:24px;
font-weight:700;
}

.section-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:20px;
cursor:pointer;
user-select:none;
}

.section-header .section-title{
font-weight:600;
font-size:18px;
color:var(--dark);
display:flex;
align-items:center;
gap:10px;
}

.section-header .collapse-icon{
transition:transform 0.3s ease;
color:var(--primary);
}

.section-header.collapsed .collapse-icon{
transform:rotate(-90deg);
}

.section-content{
transition:max-height 0.3s ease;
overflow:hidden;
max-height:0;
}

.section-content.expanded{
max-height:2000px;
}

.cms-item{
background:#F8FAFC;
border:1px solid #E2E8F0;
border-radius:18px;
padding:20px;
margin-bottom:20px;
position:relative;
}

.feature-card-item,
.program-card-item{
background:#F8FAFC;
border:1px solid #E2E8F0;
border-radius:18px;
padding:25px;
margin-bottom:20px;
position:relative;
}

.card-content{
display:flex;
gap:20px;
}

.card-icon-section{
flex:0 0 200px;
}

.card-image-section{
flex:0 0 200px;
}

.card-text-section{
flex:1;
}

.item-actions{
position:absolute;
top:15px;
right:15px;
display:flex;
gap:10px;
}

.action-btn{
border:none;
width:38px;
height:38px;
border-radius:12px;
display:flex;
align-items:center;
justify-content:center;
cursor:pointer;
}

.save-btn{
background:#DCFCE7;
color:#16A34A;
}

.delete-btn{
background:#FEE2E2;
color:#DC2626;
}

.cms-label{
font-size:13px;
font-weight:700;
margin-bottom:10px;
text-transform:uppercase;
color:#64748B;
}

.cms-input{
width:100%;
border:1px solid #CBD5E1;
border-radius:14px;
padding:14px;
outline:none;
}

.cms-input:focus{
border-color:var(--primary);
box-shadow:0 0 0 4px rgba(124,58,237,0.1);
}

.cms-textarea{
min-height:120px;
resize:vertical;
}

.image-preview{
width:180px;
height:140px;
object-fit:cover;
border-radius:14px;
margin-top:15px;
}

.add-item-btn{
background:#22C55E;
border:none;
padding:12px 20px;
border-radius:14px;
color:white;
font-weight:600;
}

.badge-type{
padding:6px 12px;
border-radius:20px;
font-size:12px;
font-weight:700;
margin-left:10px;
}

.badge-text{
background:#DBEAFE;
color:#2563EB;
}

.badge-textarea{
background:#F3E8FF;
color:#7C3AED;
}

.badge-image{
background:#DCFCE7;
color:#16A34A;
}

.toast-save{
position:fixed;
bottom:30px;
right:30px;
background:#22C55E;
color:white;
padding:16px 24px;
border-radius:14px;
display:none;
z-index:99999;
font-weight:600;
}

@media(max-width:991px){

.sidebar{
width:100%;
height:auto;
position:relative;
}

.main{
margin-left:0;
}

}

</style>

</head>

<body>
<!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>


<div class="main">

<div class="topbar">

<h1>
Homepage Visual CMS
</h1>

<a
href="../index.php"
target="_blank"
class="btn-main"
>
<i class="fas fa-eye me-2"></i>
View Website
</a>

</div>

<?php

$allowedSections = [
'header',
'hero',
'cta',
'footer'
];

$sectionTitles = [

'header' => 'Header Logo',

'hero' => 'Hero Section',

'cta' => 'CTA Section',

'footer' => 'Footer Section'

];

foreach($grouped as $section => $items){

if(!in_array($section,$allowedSections)){
continue;
}

?>

<div class="section-card">

<div class="section-header" onclick="toggleSection(this)">

<div class="section-title">

<?php echo $sectionTitles[$section]; ?>

<i class="fas fa-chevron-down collapse-icon"></i>

</div>

<?php if($section != 'header'): ?>

<button
class="add-item-btn"
onclick="addItem('<?php echo $section; ?>')"
>
<i class="fas fa-plus me-2"></i>
Add Item
</button>

<?php endif; ?>

</div>

<div class="section-content">

<?php foreach($items as $item): ?>

<div class="cms-item">

<div class="item-actions">

<button
class="action-btn save-btn"
onclick="saveItem(<?php echo $item['id']; ?>)"
>
<i class="fas fa-save"></i>
</button>

<button
class="action-btn delete-btn"
onclick="deleteItem(<?php echo $item['id']; ?>)"
>
<i class="fas fa-trash"></i>
</button>

</div>

<div class="cms-label">

<?php echo $item['content_key']; ?>

<span class="badge-type badge-<?php echo $item['content_type']; ?>">

<?php echo $item['content_type']; ?>

</span>

</div>

<?php if($item['content_type'] == 'textarea'): ?>

<textarea
class="cms-input cms-textarea"
id="item_<?php echo $item['id']; ?>"
><?php echo htmlspecialchars($item['content_value']); ?></textarea>

<?php elseif($item['content_type'] == 'image'): ?>

<input
type="file"
class="cms-input"
onchange="uploadImage(this,<?php echo $item['id']; ?>)"
>

<?php if($item['image_path']): ?>

<img
src="../uploads/homepage/<?php echo $item['image_path']; ?>"
class="image-preview"
>

<?php endif; ?>

<?php else: ?>

<input
type="text"
class="cms-input"
id="item_<?php echo $item['id']; ?>"
value="<?php echo htmlspecialchars($item['content_value']); ?>"
>

<?php endif; ?>

</div>

<?php endforeach; ?>

</div>

</div>

<?php } ?>

<!-- Features Cards Section -->
<div class="section-card">

<div class="section-header" onclick="toggleSection(this)">

<div class="section-title">

Why Choose Us - Feature Cards

<i class="fas fa-chevron-down collapse-icon"></i>

</div>

<button
class="add-item-btn"
onclick="addCard('features')"
>
<i class="fas fa-plus me-2"></i>
Add Feature Card
</button>

</div>

<div class="section-content">

<?php foreach($features as $card): ?>

<div class="feature-card-item">

<div class="item-actions">

<button
class="action-btn save-btn"
onclick="saveCard(<?php echo $card['id']; ?>)"
>
<i class="fas fa-save"></i>
</button>

<button
class="action-btn delete-btn"
onclick="deleteCard(<?php echo $card['id']; ?>)"
>
<i class="fas fa-trash"></i>
</button>

</div>

<div class="card-content">

<div class="card-icon-section">

<div class="cms-label">Icon Type</div>

<select
class="cms-input"
id="icon_type_<?php echo $card['id']; ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'icon_type', this.value)"
>
<option value="fa" <?php echo $card['icon_type'] == 'fa' ? 'selected' : ''; ?>>FontAwesome</option>
<option value="image" <?php echo $card['icon_type'] == 'image' ? 'selected' : ''; ?>>Image</option>
</select>

<?php if($card['icon_type'] == 'fa'): ?>

<div class="cms-label">FontAwesome Class</div>

<input
type="text"
class="cms-input"
id="fa_<?php echo $card['id']; ?>"
value="<?php echo htmlspecialchars($card['icon_value']); ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'icon_value', this.value)"
>

<?php else: ?>

<div class="cms-label">Icon Image</div>

<input
type="file"
class="cms-input"
onchange="uploadCardIcon(this,<?php echo $card['id']; ?>)"
>

<?php if($card['icon_value']): ?>

<img
src="../uploads/icons/<?php echo $card['icon_value']; ?>"
class="image-preview"
>

<?php endif; ?>

<?php endif; ?>

</div>

<div class="card-text-section">

<div class="cms-label">Title</div>

<input
type="text"
class="cms-input"
id="title_<?php echo $card['id']; ?>"
value="<?php echo htmlspecialchars($card['title']); ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'title', this.value)"
>

<div class="cms-label">Description</div>

<textarea
class="cms-input cms-textarea"
id="desc_<?php echo $card['id']; ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'description', this.value)"
><?php echo htmlspecialchars($card['description']); ?></textarea>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<!-- Programs Cards Section -->
<div class="section-card">

<div class="section-header" onclick="toggleSection(this)">

<div class="section-title">

Programs Section - Program Cards

<i class="fas fa-chevron-down collapse-icon"></i>

</div>

<button
class="add-item-btn"
onclick="addCard('programs')"
>
<i class="fas fa-plus me-2"></i>
Add Program Card
</button>

</div>

<div class="section-content">

<?php foreach($programs as $card): ?>

<div class="program-card-item">

<div class="item-actions">

<button
class="action-btn save-btn"
onclick="saveCard(<?php echo $card['id']; ?>)"
>
<i class="fas fa-save"></i>
</button>

<button
class="action-btn delete-btn"
onclick="deleteCard(<?php echo $card['id']; ?>)"
>
<i class="fas fa-trash"></i>
</button>

</div>

<div class="card-content">

<div class="card-image-section">

<div class="cms-label">Program Image</div>

<input
type="file"
class="cms-input"
onchange="uploadCardIcon(this,<?php echo $card['id']; ?>)"
>

<?php if($card['icon_value']): ?>

<img
src="../uploads/homepage/<?php echo $card['icon_value']; ?>"
class="image-preview"
>

<?php endif; ?>

</div>

<div class="card-text-section">

<div class="cms-label">Title</div>

<input
type="text"
class="cms-input"
id="title_<?php echo $card['id']; ?>"
value="<?php echo htmlspecialchars($card['title']); ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'title', this.value)"
>

<div class="cms-label">Age Badge</div>

<input
type="text"
class="cms-input"
id="badge_<?php echo $card['id']; ?>"
value="<?php echo htmlspecialchars($card['badge']); ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'badge', this.value)"
>

<div class="cms-label">Description</div>

<textarea
class="cms-input cms-textarea"
id="desc_<?php echo $card['id']; ?>"
onchange="updateCardField(<?php echo $card['id']; ?>, 'description', this.value)"
><?php echo htmlspecialchars($card['description']); ?></textarea>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<div class="toast-save" id="toastSave">
Saved Successfully
</div>

<script>

function toast(){

let t = document.getElementById('toastSave');

t.style.display = 'block';

setTimeout(()=>{

t.style.display = 'none';

},2000);

}

function toggleSection(header){

const sectionCard = header.parentElement;

const sectionContent = sectionCard.querySelector('.section-content');

const collapseIcon = header.querySelector('.collapse-icon');

if(sectionContent.classList.contains('collapsed')){

// Expand section
sectionContent.classList.remove('collapsed');

sectionContent.classList.add('expanded');

header.classList.remove('collapsed');

collapseIcon.classList.remove('fa-chevron-up');

collapseIcon.classList.add('fa-chevron-down');

} else {

// Collapse section
sectionContent.classList.add('collapsed');

sectionContent.classList.remove('expanded');

header.classList.add('collapsed');

collapseIcon.classList.remove('fa-chevron-down');

collapseIcon.classList.add('fa-chevron-up');

}

}

// Initialize all sections as collapsed on page load
document.addEventListener('DOMContentLoaded', function(){
    const sectionContents = document.querySelectorAll('.section-content');
    const sectionHeaders = document.querySelectorAll('.section-header');
    const collapseIcons = document.querySelectorAll('.collapse-icon');
    
    sectionContents.forEach(content => {
        content.classList.remove('expanded');
    });
    
    sectionHeaders.forEach(header => {
        header.classList.add('collapsed');
    });
    
    collapseIcons.forEach(icon => {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    });
});

function saveItem(id){

let value = '';

let el = document.getElementById('item_'+id);

if(el){
value = el.value;
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','update_content');

fd.append('id',id);

fd.append('value',value);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

toast();

});

}

function deleteItem(id){

if(!confirm('Delete this item?')){
return;
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','delete_item');

fd.append('id',id);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

location.reload();

});

}

function addItem(section){

let key =
prompt('Enter content key');

if(!key){
return;
}

let type =
prompt('Enter type (text/textarea/image)');

if(!type){
return;
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','add_item');

fd.append('section',section);

fd.append('content_key',key);

fd.append('content_type',type);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

location.reload();

});

}

function uploadImage(input,id){

let file = input.files[0];

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','upload_image');

fd.append('id',id);

fd.append('file',file);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

if(d.success){

toast();

location.reload();

}

});

}

function addCard(section){

let title = prompt('Card Title');

if(!title){
return;
}

let description = prompt('Card Description');

if(!description){
return;
}

let iconType = 'fa';
let iconValue = '';
let badge = '';

if(section === 'features'){
    iconType = prompt('Icon Type (fa/image)', 'fa');
    if(iconType === 'fa'){
        iconValue = prompt('FontAwesome Class (e.g., fa-solid fa-star)', 'fa-solid fa-star');
    }
} else if(section === 'programs'){
    badge = prompt('Age Badge (e.g., Age 2-3)', '');
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','add_card');

fd.append('section',section);

fd.append('title',title);

fd.append('description',description);

fd.append('icon_type',iconType);

fd.append('icon_value',iconValue);

fd.append('badge',badge);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

if(d.success){

toast();

location.reload();

}

});

}

function updateCardField(id, field, value){

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','update_card');

fd.append('id',id);

fd.append('field',field);

fd.append('value',value);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

if(d.success){

toast();

}

});

}

function deleteCard(id){

if(!confirm('Delete this card?')){
return;
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','delete_card');

fd.append('id',id);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

if(d.success){

location.reload();

}

});

}

function uploadCardIcon(input, id){

let file = input.files[0];

if(!file){
return;
}

let fd = new FormData();

fd.append('ajax',1);

fd.append('action','upload_image');

fd.append('id',id);

fd.append('file',file);

fetch('',{
method:'POST',
body:fd
})
.then(r=>r.json())
.then(d=>{

if(d.success){

// Update the icon_value field
updateCardField(id, 'icon_value', d.image);

location.reload();

}

});

}

</script>

</body>
</html>