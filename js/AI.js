document.addEventListener("DOMContentLoaded", () => {
    sendMessage();
});

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
    const chatBox = document.getElementById("chat-box");

    // Voeg placeholder bericht toe (optioneel)
    const botLoading = document.createElement("div");
    botLoading.classList.add("message", "bot");
    botLoading.textContent = "🔍 Getting personalized suggestions...";
    chatBox.appendChild(botLoading);
    chatBox.scrollTop = chatBox.scrollHeight;

    // Verstuur naar server (no user input, just fixed message)
    const response = await fetch("./php/chatbot.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({message: "Give me personalized book recommendations"})
    });

    const data = await response.json();
    console.log("API response:", data);

    // Haal loading weg
    botLoading.remove();

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
