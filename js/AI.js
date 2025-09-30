document.addEventListener("DOMContentLoaded", () => {
    sendMessage();
});

// document.getElementById("chat-header").onclick = () => {
//     const widget = document.getElementById("chat-widget");
//     const icon = document.querySelector("#chat-header svg");
//     const title = document.getElementById("chat-title");
//
//     // toggle open/dicht
//     widget.classList.toggle("collapsed");
//
//     // toon icon als collapsed, anders tekst
//     if (widget.classList.contains("collapsed")) {
//         icon.style.display = "inline";
//         title.style.display = "none";
//     } else {
//         icon.style.display = "none";
//         title.style.display = "inline";
//     }
// };


async function sendMessage() {
    const response = await fetch("./php/chatbot.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: "Give me personalized book recommendations" })
    });

    const data = await response.json();
    console.log(data)
    if (data.choices && data.choices[0]?.message?.content) {
        const content = data.choices[0].message.content;
        console.log("Raw content:", content);

        // Zoek alle Google Books links
        const links = content.match(/https:\/\/www\.googleapis\.com\/books\/v1\/volumes\/[A-Za-z0-9_-]+/g) || [];

        console.log("Book recommendations:", links);
    } else {
        console.log("⚠️ Geen geldige aanbevelingen ontvangen:", data);
    }
}


