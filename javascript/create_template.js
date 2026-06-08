const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let backgroundImage = null;
let memeText = "";
let textX = 30;
let textY = 30;
let isDragging = false;
const dragOffset = { x: 0, y: 0 };

const imageInput = document.getElementById("image");
const nameInput = document.getElementById("name");
const descriptionInput = document.getElementById("description");

const memeFields = document.getElementById("memeFields");

const typeRadios = document.querySelectorAll("input[name='type']");

typeRadios.forEach(radio => {
    radio.addEventListener("change", function () {
        memeFields.style.display = this.value === "meme" ? "block" : "none";
        drawCanvas();
    });
});

function setState() {
    memeText = descriptionInput ? descriptionInput.value : "";
}

function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (backgroundImage) {
        ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
    } else {
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    ctx.fillStyle = "#000";
    ctx.font = "24px Arial";

    const isMeme = document.querySelector("input[name='type']:checked")?.value === "meme";

    if (isMeme && memeText) {
        ctx.fillText(memeText, textX, textY);
    }
}

imageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
        backgroundImage = img;
        URL.revokeObjectURL(url);

        canvas.width = img.width;
        canvas.height = img.height;

        drawCanvas();
    };

    img.src = url;
});


setState();
drawCanvas();