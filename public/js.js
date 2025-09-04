function toggleGuestbookForm() {
    const formContainer = document.getElementById('guestbookForm');
    const toggleButtonContainer = document.querySelector('.toggle-button-container');

    if (formContainer.style.display === 'none') {
        // Show form, hide toggle button
        formContainer.style.display = 'block';
        toggleButtonContainer.style.display = 'none';
    } else {
        // Hide form, show toggle button
        formContainer.style.display = 'none';
        toggleButtonContainer.style.display = 'block';
    }
}

// Show the form automatically if there was an error
window.onload = function() {
    if (document.querySelector('.error')) {
        document.getElementById('guestbookForm').style.display = 'block';
        document.querySelector('.toggle-button-container').style.display = 'none';
    }
}


document.getElementById('name').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#e6ffe6'; // Lys grøn
});
document.getElementById('best').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#e6e6ff'; // Lys blå
});
document.getElementById('childhood').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#F288A4';
});
document.getElementById('wish').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#AB63F2';
});
document.getElementById('food').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#05DBF2';
});
document.getElementById('flame').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#ac87d3';
});
document.getElementById('cool').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#07F285';
});
document.getElementById('message').addEventListener('blur', function() {
    document.body.style.backgroundColor = '#F2EC91';
});



