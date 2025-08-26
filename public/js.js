
// https://tholman.com/cursor-effects/
import { fairyDustCursor } from 'https://unpkg.com/cursor-effects@latest/dist/esm.js';

window.addEventListener('load', (event) => {
    new fairyDustCursor();
});



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
