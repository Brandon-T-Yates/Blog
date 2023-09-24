<?php 
// Include necessary files and initialize variables
include('config.php');
include('includes/registration_login.php');
include('includes/head_section.php');

// Initialize variables
$title = "";
$featured_image = "";
$topic_id = "";
$body = "";
$published = "";
$errors = [];

/* - - - - - - - - - - 
-  Post actions
- - - - - - - - - - -*/

// If user clicks the create post button
if (isset($_POST['create_post'])) {
    createPost($_POST);
}

// If user clicks the Edit post button
if (isset($_GET['edit-post'])) {
    $isEditingPost = true;
    $post_id = $_GET['edit-post'];
    editPost($post_id);
}

// If user clicks the update post button
if (isset($_POST['update_post'])) {
    updatePost($_POST);
}

// If user clicks the Delete post button
if (isset($_GET['delete-post'])) {
    $post_id = $_GET['delete-post'];
    deletePost($post_id);
}

// If user clicks the publish or unpublish post button
if (isset($_GET['publish']) || isset($_GET['unpublish'])) {
    $message = "";
    if (isset($_GET['publish'])) {
        $message = "Post published successfully";
        $post_id = $_GET['publish'];
    } else if (isset($_GET['unpublish'])) {
        $message = "Post successfully unpublished";
        $post_id = $_GET['unpublish'];
    }
    togglePublishPost($post_id, $message);
}

/* - - - - - - - - - - 
-  Post functions
- - - - - - - - - - -*/

// Create a new post
function createPost($request_values) {
    global $conn, $errors, $title, $featured_image, $topic_id, $body, $published;

    // Sanitize and validate input data
    $title = esc($request_values['title']);
    $body = htmlentities(esc($request_values['body']));

    // Validate other form inputs (e.g., topic_id, published)

    // Create a unique slug for the post
    $post_slug = makeSlug($title);

    // Handle file upload
    $featured_image = $_FILES['featured_image']['name'];
    $target = "../static/images/" . basename($featured_image);

    if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
        array_push($errors, "Failed to upload image. Please check file settings for your server");
    }

    // Check if a post with the same slug already exists
    $post_check_query = "SELECT * FROM posts WHERE slug='$post_slug' LIMIT 1";
    $result = mysqli_query($conn, $post_check_query);

    if (mysqli_num_rows($result) > 0) {
        array_push($errors, "A post already exists with that title.");
    }

    // If there are no errors, insert the post into the database
    if (count($errors) == 0) {
        $query = "INSERT INTO posts (user_id, title, slug, image, body, published, created_at, updated_at) VALUES(1, '$title', '$post_slug', '$featured_image', '$body', $published, now(), now())";
        
        if (mysqli_query($conn, $query)) {
            $inserted_post_id = mysqli_insert_id($conn);

            // Create a relationship between the post and topic
            $sql = "INSERT INTO post_topic (post_id, topic_id) VALUES($inserted_post_id, $topic_id)";
            mysqli_query($conn, $sql);

            $_SESSION['message'] = "Post created successfully";
            header('location: posts.php');
            exit(0);
        }
    }
}

// Edit an existing post
function editPost($post_id) {
    global $conn, $title, $body, $published, $isEditingPost;

    $sql = "SELECT * FROM posts WHERE id=$post_id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $post = mysqli_fetch_assoc($result);

    $title = $post['title'];
    $body = $post['body'];
    $published = $post['published'];

    $isEditingPost = true;
}

// Update an existing post
function updatePost($request_values) {
    global $conn, $errors, $post_id, $title, $featured_image, $topic_id, $body, $published;

    $title = esc($request_values['title']);
    $body = esc($request_values['body']);
    $post_id = esc($request_values['post_id']);

    // Create a slug
    $post_slug = makeSlug($title);

    // Handle file upload if a new featured image is provided
    if (isset($_POST['featured_image'])) {
        $featured_image = $_FILES['featured_image']['name'];
        $target = "../static/images/" . basename($featured_image);

        if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
            array_push($errors, "Failed to upload image. Please check file settings for your server");
        }
    }

    // Validate and update the post
    if (count($errors) == 0) {
        $query = "UPDATE posts SET title='$title', slug='$post_slug', views=0, image='$featured_image', body='$body', published=$published, updated_at=now() WHERE id=$post_id";

        if (mysqli_query($conn, $query)) {
            if (isset($topic_id)) {
                $inserted_post_id = mysqli_insert_id($conn);
                $sql = "INSERT INTO post_topic (post_id, topic_id) VALUES($inserted_post_id, $topic_id)";
                mysqli_query($conn, $sql);
            }
            $_SESSION['message'] = "Post updated successfully";
            header('location: posts.php');
            exit(0);
        }
    }
}

// Delete a post
function deletePost($post_id) {
    global $conn;
    $sql = "DELETE FROM posts WHERE id=$post_id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Post successfully deleted";
        header("location: posts.php");
        exit(0);
    }
}

// Toggle the publish/unpublish status of a post
function togglePublishPost($post_id, $message) {
    global $conn;
    $sql = "UPDATE posts SET published=!published WHERE id=$post_id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = $message;
        header("location: posts.php");
        exit(0);
    }
}
