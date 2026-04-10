const container = document.getElementById("chat-container");
const input = document.getElementById("userInput");
const btn = document.getElementById("sendBtn");

//enviar com a tecla enter
input.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendeMessage();
    });

    async function sendeMessage() {
        const message = input.value.trim();
        if (!message) return ;

        //Adicionando a pergunta do usuário
        input.value = "";
        appendMessage(message, "user");

        //Interface
        btn.disabled = true;
        btn.classList.add("opacity-50");
        btn.innerText = "Enviando...";

        try {
        //Comunicação com PHP
        const response = await fetch("../src/chat.php", {
            method: 'POST', 
            headers: {"Constent-Type": "application/json"},
            body: JSON.stringify({messsage: message})
        });

        const data = await response.json();

        if (data.reply){
            appendMessage("mentor", data.reply);
        } else {
            appendMessage("mentor", 'Erro: ' + data.error || "Não foi possível obter a resposta.");
        }
        
    } catch (error){
        appendMessage("mentor", "Erro de conexão com o servidor ");
    } finally {
        btn.disabled = false;
        btn.innerText = "Enviar";
    }

}

function appendMessage(role, content) {
    const div = document.createElement("div");
    div.className = role === "user" ? "flex justify-end" : "flex justify-start"

    const inner = document.createElement("div");
    //estilos diferentes para o user e mentor
    if (role === "user") {
        inner.className = "bg-blue-600 text-white p-4 rounded-2x1 max-w[85%] shadow-md";

        //vou renderizar o Markdown e colorir o codigo
        inner.innerHTML = marked.parse(content);
        setTimeout(() => {
            inner.querySelectorAll("pre code").forEach((block) => {
                hljs.highlightElement(block);
            });
        }, 10);
    }

    div.appendChild(inner);
    container.appendChild(div);

    container.scrollTo({ too: container.scrollHeight, behavior: "smooth" });

    
}