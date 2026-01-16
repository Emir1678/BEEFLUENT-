// assets/js/chat.js

const chatBox = document.getElementById("chatBox");
const msgInput = document.getElementById("msg");
const sendBtn = document.getElementById("sendBtn");
const statusDiv = document.getElementById("status");

/**
 * SESSION SUPPORT (Option A)
 * This JS is "session-aware", but backward-compatible.
 *
 * You can provide session id in ANY of these ways:
 *  1) <body data-session-id="...">
 *  2) <meta name="chat-session-id" content="...">
 *  3) window.__CHAT_SESSION_ID = "..."
 */
function getSessionId() {
  // 3) window global
  if (typeof window.__CHAT_SESSION_ID === "string" && window.__CHAT_SESSION_ID.trim() !== "") {
    return window.__CHAT_SESSION_ID.trim();
  }

  // 1) data attribute
  const sidFromBody = document.body?.dataset?.sessionId;
  if (typeof sidFromBody === "string" && sidFromBody.trim() !== "") {
    return sidFromBody.trim();
  }

  // 2) meta tag
  const meta = document.querySelector('meta[name="chat-session-id"]');
  const sidFromMeta = meta?.getAttribute("content");
  if (typeof sidFromMeta === "string" && sidFromMeta.trim() !== "") {
    return sidFromMeta.trim();
  }

  return null;
}

function setStatus(text) {
  if (!statusDiv) return;
  statusDiv.textContent = text || "";
}

/**
 * Appends a message bubble to the chat UI.
 * @param {string} role - "user" or "ai"
 * @param {string} text - message content
 */
function appendMessage(role, text) {
  if (!chatBox) return;

  const msgDiv = document.createElement("div");
  msgDiv.className = role === "user" ? "message user-message" : "message ai-message";
  msgDiv.textContent = text;

  chatBox.appendChild(msgDiv);
  chatBox.scrollTop = chatBox.scrollHeight;
}

/**
 * Typing indicator shown while waiting for AI response.
 */
function showTypingIndicator() {
  if (!chatBox) return;

  const loader = document.createElement("div");
  loader.className = "message ai-message typing-loader";
  loader.id = "ai-typing";
  loader.innerHTML = "• • •";
  chatBox.appendChild(loader);
  chatBox.scrollTop = chatBox.scrollHeight;
}

function removeTypingIndicator() {
  const indicator = document.getElementById("ai-typing");
  if (indicator) indicator.remove();
}

let isSending = false;

async function handleSendMessage() {
  if (isSending) return;

  const text = (msgInput?.value || "").trim();
  if (!text) return;

  isSending = true;
  if (sendBtn) sendBtn.disabled = true;

  // 1) Add user message
  appendMessage("user", text);
  if (msgInput) msgInput.value = "";
  setStatus("");

  // 2) Show typing indicator
  showTypingIndicator();

  // Include session_id if available (backward-compatible)
  const sessionId = getSessionId();
  const payload = sessionId ? { message: text, session_id: sessionId } : { message: text };

  try {
    const response = await fetch("api/ai_chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
      cache: "no-store",
    });

    removeTypingIndicator();

    const raw = await response.text();
    let data = null;

    try {
      data = JSON.parse(raw);
    } catch {
      appendMessage("ai", "Sorry — I couldn’t read the server response.");
      return;
    }

    if (!response.ok) {
      const errMsg = (data && (data.error || data.message)) ? String(data.error || data.message) : "Server error.";
      appendMessage("ai", `Error: ${errMsg}`);
      return;
    }

    // 3) Add AI reply
    if (data && typeof data.reply === "string" && data.reply.trim() !== "") {
      appendMessage("ai", data.reply);
    } else {
      appendMessage("ai", "Sorry — I can’t reply right now.");
    }

    // If backend returns session_id, store it (useful for first message of new session)
    if (data && typeof data.session_id === "string" && data.session_id.trim() !== "") {
      const sid = data.session_id.trim();
      window.__CHAT_SESSION_ID = sid;
      if (document.body) document.body.dataset.sessionId = sid;

      // Small UX: show we’re continuing the same session
      setStatus("Session saved ✅");
      setTimeout(() => setStatus(""), 1200);
    }
  } catch (err) {
    console.error(err);
    removeTypingIndicator();
    appendMessage("ai", "A connection error occurred.");
  } finally {
    isSending = false;
    if (sendBtn) sendBtn.disabled = false;
  }
}

// Click listener
sendBtn?.addEventListener("click", handleSendMessage);

// Enter behavior:
// - Enter: send
// - Shift+Enter: newline (so user can write multi-line)
msgInput?.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    handleSendMessage();
  }
});

// Optional: show status if page already has session id
(function initSessionStatus() {
  const sid = getSessionId();
  if (sid) setStatus("Continuing previous session…");
})();

