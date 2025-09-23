window.addEventListener('load', init)

let showLogin
let logIn
let registring
let showRegister

function init() {
    showLogin = document.querySelector('#showLogin')
    logIn = document.querySelector('#log-in')
    showRegister = document.querySelector('#showRegister')
    registring = document.querySelector('#registring')
    showLogin.addEventListener('click', signInHandler)
    showRegister.addEventListener('click', registerHandler)
}

function signInHandler(e) {
    e.preventDefault()
    console.log(e)
    logIn.className = ""
    registring.className = "hidden"
}

function registerHandler(e) {
    e.preventDefault()
    registring.className = ""
    logIn.className = "hidden"
}