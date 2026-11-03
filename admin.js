const body = document.body;

const darkButton = document.getElementById("toggle-dark");

// Cargar modo guardado
if(localStorage.getItem("mode") === "dark"){
    body.classList.add("dark");

    if(darkButton){
        darkButton.innerHTML = '<i class="fa-solid fa-sun"></i>';
    }
}

// Cambiar modo
if(darkButton){

    darkButton.addEventListener("click", () =>{

        body.classList.toggle("dark");

        if(body.classList.contains("dark")){

            localStorage.setItem("mode","dark");

            darkButton.innerHTML = '<i class="fa-solid fa-sun"></i>';

        }else{

            localStorage.setItem("mode","light");

            darkButton.innerHTML = '<i class="fa-solid fa-moon"></i>';

        }

    });

}