<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
</head>
<body>

</body>







<script type="module">
  // Import the functions you need from the SDKs you need
  import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.3/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.12.3/firebase-analytics.js";
  // TODO: Add SDKs for Firebase products that you want to use
  // https://firebase.google.com/docs/web/setup#available-libraries

  // Your web app's Firebase configuration
  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
  const firebaseConfig = {
    apiKey: "AIzaSyCvOolMj9Gw73ks-sunh7KwtgREsqUepQA",
    authDomain: "gmsrealtimelocations.firebaseapp.com",
    databaseURL: "https://gmsrealtimelocations-default-rtdb.firebaseio.com",
    projectId: "gmsrealtimelocations",
    storageBucket: "gmsrealtimelocations.appspot.com",
    messagingSenderId: "613376088420",
    appId: "1:613376088420:web:99e7dce407ec3c8e9d0271",
    measurementId: "G-DWG3E1VPEQ"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
</script>
</html>