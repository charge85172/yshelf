document.getElementById("chat-header").onclick = () => {
    const widget = document.getElementById("chat-widget");
    const icon = document.querySelector("#chat-header svg");
    const title = document.getElementById("chat-title");

    // toggle open/dicht
    widget.classList.toggle("collapsed");

    // toon icon als collapsed, anders tekst
    if (widget.classList.contains("collapsed")) {
        icon.style.display = "inline";
        title.style.display = "none";
    } else {
        icon.style.display = "none";
        title.style.display = "inline";
    }
};


async function sendMessage() {
    const userText = document.getElementById("userInput").value;
    const chatBox = document.getElementById("chat-box");

    if (!userText.trim()) return;

    // Voeg bericht van gebruiker toe
    const userMsg = document.createElement("div");
    userMsg.classList.add("message", "user");
    userMsg.textContent = userText;
    chatBox.appendChild(userMsg);
    chatBox.scrollTop = chatBox.scrollHeight;

    document.getElementById("userInput").value = "";

    // Verstuur naar server
    const response = await fetch("./php/chatbot.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({message: userText})
    });

    const data = await response.json();
    console.log("API response:", data);

    // Voeg bericht van bot toe
    const botMsg = document.createElement("div");
    botMsg.classList.add("message", "bot");

    if (data.choices && data.choices[0]) {
        botMsg.textContent = data.choices[0].message.content;
    } else {
        botMsg.textContent = "⚠️ Fout: " + JSON.stringify(data);
    }

    chatBox.appendChild(botMsg);
    chatBox.scrollTop = chatBox.scrollHeight;
}

