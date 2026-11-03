const container = document.getElementById("container");
const registerBtn = document.getElementById("register");
const loginBtn = document.getElementById("login");

registerBtn.addEventListener("click", ()=> {
    container.classList.add("active");
});

loginBtn.addEventListener("click", ()=> {
    container.classList.remove("active");
});

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