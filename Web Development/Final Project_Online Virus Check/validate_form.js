

/**
 * validate admin login
 * @param {type} form
 * @returns {Boolean}
 */
function validateLogin(form){
    fail = validateUsername(form.login_username.value)
    fail+= validatePassword(form.login_pw.value)
    if(fail == "") return true
    alert(fail)
    return false
}

/**
 * 
 * @param {type} form
 * @returns {Boolean}
 */
function validateSignup(form){

    fail = validateUsername(form.signup_username.value)
    fail+= validatePassword(form.signup_pw.value)
    if(form.signup_pw.value !== form.confirm_pw.value) {
        fail+= "confirm password should be the same with password.\n"
    }
    if(fail == "") return true
    alert(fail)
    return false
}

function validateUploadFile1(form){
    
    //document.write(form.file_name.value)
    fail = validateFileName(form.file_name.value)
    
    uploadFile = document.getElementById("upload_file") //only the getElementById once
    //fail+= validateFileSize(form.upload_file) //use this or
    fail+= validateFileSize(uploadFile) //use this
    if(fail == "") {return true}
    
    alert(fail)
    return false
}
    
function validateUploadFile2(){
    
    uploadFile = document.getElementById("putative_file") //only getElementById once
    //fail+= validateFileSize(form.upload_file) //use this or
    fail = validateFileSize(uploadFile) //use this
    if(fail == "") {return true}
    alert(fail)
    return false
}

/**
 * username must be longer than 6, don't contain any space, and only contain a-z, A-Z, 0-9, - and _
 * @param {String} username
 * @returns {String}
 */
function validateUsername(username){
    if(username.indexOf(" ")!==-1){ return "username/password format error.\n" }
    else if(username.length < 6){ return "username/password format error.\n" }
    else if(/[^a-zA-Z0-9_-]/.test(username)){ return "username/password format error.\n"}
    return ""
}
/**
 * password must be longer than 6, and as least contain one lowercase, 
 * one uppercase, and one digital number
 * @param {String} pw
 * @returns {String}
 */
function validatePassword(pw){
    if(pw.indexOf(" ")!==-1){ return "username/password format error.\n" }
    else if(pw.length<6){ return "username/password format error.\n" }
    else if(!(/[a-z]/.test(pw) && /[A-Z]/.test(pw) && /[0-9]/.test(pw))){
        return "username/password format error.\n"
    }
    return ""
}


/**
 * file name can only contain a-z, A-Z, 0-9
 * @param {String} fileName
 * @returns {String}
 */
function validateFileName(fileName){
    
    if(fileName.indexOf(" ")!==-1){ return "should not contain space.\n" }
    else if(fileName === ""){return "should not empty.\n"}
    else if(/[^a-zA-Z0-9]/.test(fileName)){ return "file name can only contain a-z, A-Z, 0-9\n" }
    return ""
}

/**
 * the size should no smaller than 20
 * @param {DOM} uploadFile
 * @returns {String}
 */
function validateFileSize(uploadFile){
    //uploadFile.files[0].size : can get the bytes of file
    if(uploadFile.files[0] === undefined){return "cannot find the file.\n"}
    else if(uploadFile.files[0].size < 20 ){ return "the file is too small.\n"}
    
    return ""
}


