
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";
import { getDatabase } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-database.js";

const firebaseConfig = {
    apiKey: "AIzaSyDwEoymnFCh-iQXtYrSEFjFACMrL8GRnb8",
    authDomain: "dividendoplus-app.firebaseapp.com",
    databaseURL: "https://dividendoplus-app-default-rtdb.firebaseio.com",
    projectId: "dividendoplus-app",
    storageBucket: "dividendoplus-app.firebasestorage.app",
    messagingSenderId: "350665567645",
    appId: "1:350665567645:web:cba2d707864865a212d374"
};

const app = initializeApp(firebaseConfig);

export const auth = getAuth(app);
export const database = getDatabase(app);