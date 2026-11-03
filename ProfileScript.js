const body = document.querySelector("body"),
      sidebar = body.querySelector(".sidebar"),
      toggle = body.querySelector(".toggle"),
      searchBtn = body.querySelector(".search-box"),
      modeSwtich = body.querySelector(".toggle-switch"),
      modeText = body.querySelector(".mode-text");


// ===== SIDEBAR =====
if(localStorage.getItem("sidebar") === "close"){
    sidebar.classList.add("close");
}

toggle.addEventListener("click", () =>{
    sidebar.classList.toggle("close");

    if(sidebar.classList.contains("close")){
        localStorage.setItem("sidebar", "close");
    }else{
        localStorage.setItem("sidebar", "open");
    }
});


// ===== DARK MODE =====
if(localStorage.getItem("mode") === "dark"){
    body.classList.add("dark");
    modeText.innerText = "Light Mode";
}

modeSwtich.addEventListener("click", () =>{
    body.classList.toggle("dark");

    if(body.classList.contains("dark")){
        localStorage.setItem("mode", "dark");
        modeText.innerText = "Light Mode";
    }else{
        localStorage.setItem("mode", "light");
        modeText.innerText = "Dark Mode";
    }
});

// Elementos del DOM del Modal
// ================= MANEJO DE MODAL =================
const profileModal = document.getElementById('profileModal');
const openModalBtn = document.getElementById('openModalBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const saveProfileBtn = document.getElementById('saveProfileBtn');
const userNameDisplay = document.getElementById('userNameDisplay');
const userEmailDisplay = document.getElementById('userEmailDisplay');
const inputName = document.getElementById('inputName');
const inputEmail = document.getElementById('inputEmail');

openModalBtn.addEventListener('click', () => profileModal.classList.add('active'));
closeModalBtn.addEventListener('click', () => profileModal.classList.remove('active'));
profileModal.addEventListener('click', (e) => { if(e.target === profileModal) profileModal.classList.remove('active'); });

saveProfileBtn.addEventListener('click', () => {
    userNameDisplay.textContent = inputName.value;
    userEmailDisplay.textContent = inputEmail.value;
    profileModal.classList.remove('active');
});


// ================= CAMBIAR FONDO =================
const bgInput = document.getElementById('bgInput');
const profileHeader = document.getElementById('profileHeader');

bgInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function() {
            profileHeader.style.backgroundImage = `url(${this.result})`;
        }
        reader.readAsDataURL(file);
    }
});


// ================= CAMBIAR AVATAR =================
const avatarInput = document.getElementById('avatarInput');
const avatarImg = document.getElementById('avatarImg');

avatarInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function() {
            avatarImg.src = this.result;
        }
        reader.readAsDataURL(file);
    }
});


// ================= CAMBIAR FOTOS/GIFS DEL EXPOSITOR =================
const gameInputs = document.querySelectorAll('.game-input');

gameInputs.forEach(input => {
    input.addEventListener('change', function() {
        const file = this.files[0];
        const targetId = this.getAttribute('data-target');
        const targetImg = document.getElementById(targetId);
        
        if (file && targetImg) {
            const reader = new FileReader();
            reader.onload = function() {
                targetImg.src = this.result;
            }
            reader.readAsDataURL(file);
        }
    });
});

document.getElementById('closeModalBtn').addEventListener('click', function(e) {
    e.preventDefault(); // Evita cualquier comportamiento extraño
    document.getElementById('profileModal').style.display = 'none'; // O la lógica que uses para ocultarlo
});