window.addEventListener('load', init)

let showLogin
let logIn
let registring
let showRegister
let goingToRegister

function init() {
    showLogin = document.querySelector('#showLogin')
    logIn = document.querySelector('#log-in')
    showRegister = document.querySelector('#showRegister')
    registring = document.querySelector('#registring')
    goingToRegister = document.querySelector('#registerButton')

    showLogin.addEventListener('click', signInHandler)
    showRegister.addEventListener('click', registerHandler)
    goingToRegister.addEventListener('click', preventHandler)
}

function signInHandler(e) {
    e.preventDefault()
    console.log(e)
    logIn.className = ""
    registring.className = "hidden"
    showRegister.className = ""
    showLogin.className = "hidden"
}

function registerHandler(e) {
    e.preventDefault()
    registring.className = ""
    logIn.className = "hidden"
    showRegister.className = "hidden"
    showLogin.className = ""
}

function preventHandler(e) {

}