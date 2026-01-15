// assets/js/chat.js

const chatBox = document.getElementById("chatBox");
const msgInput = document.getElementById("msg");
const sendBtn = document.getElementById("sendBtn");
const statusDiv = document.getElementById("status");

/**
 * Mesajı ekrana ekleyen ana fonksiyon
 * @param {string} role - 'user' veya 'ai'
 * @param {string} text - Mesaj içeriği
 */
function appendMessage(role, text) {
    const msgDiv = document.createElement("div");
    
    // PHP'deki CSS sınıflarına tam uyum:
    // Kullanıcı ise 'user-message' (Sağ), AI ise 'ai-message' (Sol)
    msgDiv.className = role === "user" ? "message user-message" : "message ai-message";
    
    // Mesaj içeriğini güvenli bir şekilde ekle
    msgDiv.textContent = text;
    
    chatBox.appendChild(msgDiv);
    
    // Her yeni mesajda en aşağı kaydır
    chatBox.scrollTop = chatBox.scrollHeight;
}

/**
 * AI cevap verirken görünecek olan 'Yazıyor...' baloncuğu
 */
function showTypingIndicator() {
    const loader = document.createElement("div");
    loader.className = "message ai-message typing-loader";
    loader.id = "ai-typing";
    loader.innerHTML = "• • •"; // Basit ama şık bir animasyon karakteri
    chatBox.appendChild(loader);
    chatBox.scrollTop = chatBox.scrollHeight;
}

async function handleSendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    // 1. Kullanıcı mesajını SAĞA ekle
    appendMessage("user", text);
    msgInput.value = "";
    
    // 2. Yazıyor durumunu göster
    showTypingIndicator();

    try {
        const response = await fetch("api/ai_chat.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: text })
        });

        const data = await response.json();

        // 3. 'Yazıyor' balonunu kaldır
        const indicator = document.getElementById("ai-typing");
        if (indicator) indicator.remove();

        // 4. AI cevabını SOLA ekle
        if (data.reply) {
            appendMessage("ai", data.reply);
        } else {
            appendMessage("ai", "Üzgünüm, şu an cevap veremiyorum.");
        }

    } catch (err) {
        console.error(err);
        const indicator = document.getElementById("ai-typing");
        if (indicator) indicator.remove();
        appendMessage("ai", "Bağlantı hatası oluştu.");
    }
}

// Buton tıklama ve Enter tuşu dinleyicileri
sendBtn.addEventListener("click", handleSendMessage);
msgInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") handleSendMessage();
});
