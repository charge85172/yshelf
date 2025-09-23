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
    const chatBox = document.getElementById("chat");

    const response = await fetch("./php/chatbot.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({message: userText})
    });

    const data = await response.json();
    console.log("API response:", data);

    if (data.choices && data.choices[0]) {
        chatBox.textContent += "Bot: " + data.choices[0].message.content + "\n";
    } else {
        chatBox.textContent += "⚠️ Fout: " + JSON.stringify(data) + "\n";
    }
}
