document.querySelector("form").addEventListener("submit" ,function(event){
    const password = document.getElementById("ForgotPass1").value;
    const confirmPassword =  document.getElementById("ConfirmPass").value;
    

    if(password === "" || confirmPassword === ""){
        alert("Please fill all fields")
        
    }else if(password !== confirmPassword){
        event.preventDefault();
        alert("Passwords do not match");
    }
})  

