
function confirmLogout(event) {
    event.preventDefault(); // Prevent the default link behavior
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'index.html'; // Redirect to logout.php if confirmed
    }
}
