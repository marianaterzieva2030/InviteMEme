const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let uploadedImage = null;
let backgroundImage = null;


const texts = [
    { id: "title", text: "Тема", x: 30, y: 40 },
    { id: "date", text: "Дата", x: 30, y: 90 },
    { id: "room", text: "Зала", x: 30, y: 140 },
    { id: "presenter", text: "Презентиращ", x: 30, y: 190 },
    { id: "description", text: "Описание", x: 30, y: 240 }
];


const titleInput = document.getElementById("titleInput");
const dateInput = document.getElementById("dateInput");
const timeInput = document.getElementById("timeInput");
const roomInput = document.getElementById("roomInput");
const presenterInput = document.getElementById("presenterInput");
const descriptionInput = document.getElementById("descriptionInput");
const colorInput = document.getElementById("colorInput");
const sizeInput = document.getElementById("sizeInput");
const fontInput = document.getElementById("fontInput");
const imageInput = document.getElementById("imageInput");
const templateSelect = document.getElementById("templateSelect");
const canvasData = document.getElementById("canvasData");
const inviteForm = document.getElementById("inviteForm");
const shareBtn = document.getElementById("shareBtn");


let activeText = null;
let offsetX = 0;
let offsetY = 0;


function updateTextsFromInputs() {
    texts[0].text = "Тема: " + titleInput.value;
    texts[1].text = "Дата: " + dateInput.value + " " + timeInput.value;
    texts[2].text = "Зала: " + roomInput.value;
    texts[3].text = "Презентиращ: " + presenterInput.value;
    texts[4].text = descriptionInput.value;
}

function getTextBlockMetrics() {
    ctx.font = `${sizeInput.value}px ${fontInput.value}`;
    const lines = getTextLines();
    let maxWidth = 0;
    lines.forEach(line => {
        maxWidth = Math.max(maxWidth, ctx.measureText(line).width);
    });
    const height = lines.length * (parseInt(sizeInput.value, 5) + lineSpacing) - lineSpacing;
    return {
        x: textX,
        y: textY,
        width: maxWidth + 20,
        height: height + 20,
    };
} 

function drawBackground(img) {
    const scale = Math.max(
        canvas.width / img.width,
        canvas.height / img.height
    );

    const w = img.width * scale;
    const h = img.height * scale;

    const x = (canvas.width - w) / 2;
    const y = (canvas.height - h) / 2;

    ctx.drawImage(img, x, y, w, h);
}


function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (uploadedImage) {
        ctx.drawImage(uploadedImage, 0, 0, canvas.width, canvas.height);
    } else if (backgroundImage) {
        ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
    } else {
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    ctx.fillStyle = colorInput.value;
    ctx.font = `${sizeInput.value}px ${fontInput.value}`;
    ctx.textBaseline = "top";

    texts.forEach(t => {
        ctx.fillText(t.text, t.x, t.y);
    });
}


function getTextAt(x, y) {
    return texts.find(t => {
        const w = ctx.measureText(t.text).width;
        const h = parseInt(sizeInput.value);

        return (
            x >= t.x &&
            x <= t.x + w &&
            y >= t.y &&
            y <= t.y + h
        );
    });
}


function getMousePos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY
    };
}


canvas.addEventListener("pointerdown", (e) => {
    const p = getMousePos(e);
    const hit = getTextAt(p.x, p.y);

    if (hit) {
        activeText = hit;
        offsetX = p.x - hit.x;
        offsetY = p.y - hit.y;
    }
});

canvas.addEventListener("pointermove", (e) => {
    if (!activeText) return;

    const p = getMousePos(e);

    activeText.x = p.x - offsetX;
    activeText.y = p.y - offsetY;

    drawCanvas();
});

canvas.addEventListener("pointerup", () => {
    activeText = null;
});


titleInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

dateInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

timeInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

roomInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

presenterInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

descriptionInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});


imageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const img = new Image();
    const url = URL.createObjectURL(file);

    img.src = url;

    img.onload = () => {
        uploadedImage = img;
        URL.revokeObjectURL(url);

        const imgW = uploadedImage.width;
        const imgH = uploadedImage.height;

        const maxSize = 600;

        let targetWidth;
        let targetHeight;

        if (imgH > imgW) {
            targetHeight = maxSize;
            targetWidth = (imgW / imgH) * maxSize;
        }
        else {
            targetWidth = maxSize;
            targetHeight = (imgH / imgW) * maxSize;
        }
        canvas.width = targetWidth;
        canvas.height = targetHeight;

        const preview = document.getElementById("previewArea");
        preview.style.width = targetWidth + "px";
        preview.style.height = targetHeight + "px";

        drawCanvas();
    }
});
    templateSelect.addEventListener("change", function () {
        const imgPath = this.selectedOptions[0]?.dataset?.image;

        if (!imgPath) return;

        const img = new Image();
        img.src = imgPath;

        img.onload = () => {
            backgroundImage = img;
            drawCanvas();
        };
    });

    // -------------------- SAVE --------------------
    inviteForm.addEventListener("submit", () => {
        canvasData.value = canvas.toDataURL("image/png");
    });

    // -------------------- SHARE --------------------
    shareBtn.addEventListener("click", async () => {
        const blob = await new Promise(res => canvas.toBlob(res, "image/png"));

        const file = new File([blob], "invite.png", { type: "image/png" });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                title: "Покана",
                files: [file]
            });
        }
    });


    updateTextsFromInputs();
    drawCanvas();

