<?php
require_once 'config.php';

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM patients WHERE id=$id");
$data = mysqli_fetch_assoc($result);
?>

<form method="POST">
  <input type="text" name="name" value="<?php echo $data['name']; ?>">
  <button name="update">Update</button>
</form>

<?php
if(isset($_POST['update'])){
  $name = $_POST['name'];
  mysqli_query($conn, "UPDATE patients SET name='$name' WHERE id=$id");
  header("Location: index.php");
}
?>