const createPreviewUrl = (file) => {
    if (!(file instanceof File)) {
        return null;
    }

    try {
        return URL.createObjectURL(file);
    } catch {
        return null;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-report-form-root]');

    if (!root) {
        return;
    }

    const form = root.querySelector('[data-report-draft-form]');
    const isCivilian = form?.dataset.reportRole === 'civilian';
    const geoButton = root.querySelector('[data-geo-fill-button]');
    const latitudeInput = root.querySelector('[data-geo-latitude]');
    const longitudeInput = root.querySelector('[data-geo-longitude]');
    const locationInput = root.querySelector('#location_text');
    const descriptionInput = root.querySelector('[data-required-description]');
    const geoStatus = root.querySelector('[data-geo-status]');
    const submitButton = root.querySelector('[data-report-submit]');
    const submitStatus = root.querySelector('[data-capture-submit-status]');
    const evidenceInput = root.querySelector('input[name="evidence"]');
    const selfieInput = root.querySelector('input[name="selfie"]');
    const photoCaptureInput = root.querySelector('[data-capture-photo-input]');
    const videoCaptureInput = root.querySelector('[data-capture-video-input]');
    const selfieCaptureInput = root.querySelector('[data-capture-selfie-input]');
    const evidencePreviewImage = root.querySelector('[data-evidence-preview-image]');
    const evidencePreviewVideo = root.querySelector('[data-evidence-preview-video]');
    const evidencePreviewName = root.querySelector('[data-evidence-preview-name]');
    const evidenceWarningCard = root.querySelector('[data-evidence-preview-warning-card]');
    const evidenceWarningTag = root.querySelector('[data-evidence-preview-warning-tag]');
    const evidenceWarningTitle = root.querySelector('[data-evidence-preview-warning-title]');
    const evidenceWarningBody = root.querySelector('[data-evidence-preview-warning-body]');
    const evidenceWarningList = root.querySelector('[data-evidence-preview-warning-list]');
    const evidenceReplaceTrigger = root.querySelector('[data-evidence-replace-trigger]');
    const warningDismissTrigger = root.querySelector('[data-warning-dismiss-trigger]');
    const selfiePreviewImage = root.querySelector('[data-selfie-preview-image]');
    const selfiePreviewName = root.querySelector('[data-selfie-preview-name]');
    const selfieCameraModal = root.querySelector('[data-selfie-camera-modal]');
    const selfieCameraPreview = root.querySelector('[data-selfie-camera-preview]');
    const selfieCameraCanvas = root.querySelector('[data-selfie-camera-canvas]');
    const selfieCameraStatus = root.querySelector('[data-selfie-camera-status]');
    const selfieCameraCaptureButton = root.querySelector('[data-selfie-camera-capture]');
    const selfieCameraRetryButton = root.querySelector('[data-selfie-camera-retry]');
    const selfieCameraFallbackButton = root.querySelector('[data-selfie-camera-fallback]');
    const selfieCameraCancelButton = root.querySelector('[data-selfie-camera-cancel]');
    const evidenceBadge = root.querySelector('[data-capture-badge="evidence"]');
    const videoBadge = root.querySelector('[data-capture-badge="video"]');
    const selfieBadge = root.querySelector('[data-capture-badge="selfie"]');
    const gpsBadge = root.querySelector('[data-capture-badge="gps"]');
    const photoRequirement = root.querySelector('[data-requirement-status="photo"]');
    const selfieRequirement = root.querySelector('[data-requirement-status="selfie"]');
    const gpsRequirement = root.querySelector('[data-requirement-status="gps"]');
    const descriptionRequirement = root.querySelector('[data-requirement-status="description"]');
    const triggerButtons = root.querySelectorAll('[data-capture-trigger]');
    const gpsFocusButton = root.querySelector('[data-capture-trigger="gps"]');

    let evidencePreviewUrl = null;
    let selfiePreviewUrl = null;
    let selfieCameraStream = null;
    let evidenceInspectionToken = 0;
    let pendingSubmitAfterSelfie = false;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const setGeoStatus = (message) => {
        if (geoStatus) {
            geoStatus.textContent = message;
        }
    };

    const setBadgeState = (badge, tone, label) => {
        if (!badge) {
            return;
        }

        badge.className = `tag ${tone}`;
        badge.textContent = label;
    };

    const clearInputFile = (input) => {
        if (input instanceof HTMLInputElement) {
            input.value = '';
        }
    };

    const getActiveEvidenceFile = () =>
        photoCaptureInput?.files?.[0]
        ?? videoCaptureInput?.files?.[0]
        ?? evidenceInput?.files?.[0]
        ?? null;

    const getActiveVideoFile = () => videoCaptureInput?.files?.[0] ?? null;

    const getActiveSelfieFile = () =>
        selfieCaptureInput?.files?.[0]
        ?? selfieInput?.files?.[0]
        ?? null;

    const hasRequiredPhoto = () => {
        const activeEvidence = getActiveEvidenceFile();

        return activeEvidence instanceof File && activeEvidence.type.startsWith('image/');
    };

    const hasRequiredSelfie = () => getActiveSelfieFile() instanceof File;

    const hasRequiredGps = () =>
        latitudeInput instanceof HTMLInputElement
        && longitudeInput instanceof HTMLInputElement
        && latitudeInput.value.trim() !== ''
        && longitudeInput.value.trim() !== '';

    const hasRequiredDescription = () =>
        descriptionInput instanceof HTMLTextAreaElement
        && descriptionInput.value.trim().length > 0;

    const revokePreviewUrl = (value) => {
        if (value) {
            URL.revokeObjectURL(value);
        }
    };

    const hideEvidenceWarning = () => {
        if (!(evidenceWarningCard instanceof HTMLElement)) {
            return;
        }

        evidenceWarningCard.hidden = true;
        evidenceWarningCard.dataset.warningTone = 'amber';

        if (evidenceWarningTag) {
            evidenceWarningTag.className = 'tag amber';
            evidenceWarningTag.textContent = 'Preview check';
        }

        if (evidenceWarningTitle) {
            evidenceWarningTitle.textContent = 'Review the selected evidence photo.';
        }

        if (evidenceWarningBody) {
            evidenceWarningBody.textContent = 'The selected file may not look like a real accident or emergency scene photo. Replace it now if it seems unrelated.';
        }

        if (evidenceWarningList) {
            evidenceWarningList.innerHTML = '';
        }
    };

    const showEvidenceWarning = (tone, tagLabel, title, body, reasons = []) => {
        if (!(evidenceWarningCard instanceof HTMLElement)) {
            return;
        }

        evidenceWarningCard.hidden = false;
        evidenceWarningCard.dataset.warningTone = tone;

        if (evidenceWarningTag) {
            evidenceWarningTag.className = `tag ${tone}`;
            evidenceWarningTag.textContent = tagLabel;
        }

        if (evidenceWarningTitle) {
            evidenceWarningTitle.textContent = title;
        }

        if (evidenceWarningBody) {
            evidenceWarningBody.textContent = body;
        }

        if (evidenceWarningList) {
            evidenceWarningList.innerHTML = reasons
                .map((reason) => `<li>${reason}</li>`)
                .join('');
        }
    };

    const suspiciousEvidenceTokens = [
        'screen',
        'screenshot',
        'icon',
        'logo',
        'mockup',
        'welcome',
        'login',
        'register',
        'profile',
        'dashboard',
        'settings',
        'selfie',
        'portrait',
        'avatar',
    ];

    const getImageDimensions = (file) => new Promise((resolve) => {
        if (!(file instanceof File) || !file.type.startsWith('image/')) {
            resolve(null);
            return;
        }

        const probeUrl = createPreviewUrl(file);
        if (!probeUrl) {
            resolve(null);
            return;
        }

        const image = new Image();
        image.onload = () => {
            resolve({
                width: image.naturalWidth,
                height: image.naturalHeight,
            });
            URL.revokeObjectURL(probeUrl);
        };
        image.onerror = () => {
            resolve(null);
            URL.revokeObjectURL(probeUrl);
        };
        image.src = probeUrl;
    });

    const inspectEvidenceFile = async (file) => {
        evidenceInspectionToken += 1;
        const currentToken = evidenceInspectionToken;

        if (!isCivilian || !(file instanceof File)) {
            hideEvidenceWarning();
            return;
        }

        if (file.type.startsWith('video/')) {
            showEvidenceWarning(
                'amber',
                'Video selected',
                'Add a real scene photo too.',
                'Video can help responders, but the civilian flow still requires one clear accident or emergency scene photo.',
                [
                    'A video alone does not satisfy the required photo evidence step.',
                    'Capture one still image of the actual scene before you send the report.',
                ],
            );
            return;
        }

        if (!file.type.startsWith('image/')) {
            showEvidenceWarning(
                'red',
                'Invalid file',
                'Use a real photo file only.',
                'The selected file does not look like a normal camera photo. Replace it with a real accident or emergency image.',
                ['Use JPG, JPEG, PNG, or WEBP camera photos for the scene evidence step.'],
            );
            return;
        }

        const reasons = [];
        const lowerName = file.name.toLowerCase();
        if (suspiciousEvidenceTokens.some((token) => lowerName.includes(token))) {
            reasons.push('The file name looks similar to a screenshot, icon, profile image, or app asset.');
        }

        const dimensions = await getImageDimensions(file);
        if (currentToken !== evidenceInspectionToken) {
            return;
        }

        if (dimensions) {
            const shortestSide = Math.min(dimensions.width, dimensions.height);
            const longestSide = Math.max(dimensions.width, dimensions.height);
            const aspectRatio = longestSide / Math.max(shortestSide, 1);

            if (shortestSide < 320) {
                reasons.push(`The image is very small (${dimensions.width}x${dimensions.height}), which often means it is an icon, screenshot, or low-detail file.`);
            }

            if (aspectRatio > 2.15) {
                reasons.push(`The image shape is unusually tall or wide (${dimensions.width}x${dimensions.height}), which often happens with screenshots instead of camera scene photos.`);
            }
        }

        if (reasons.length === 0) {
            hideEvidenceWarning();
            return;
        }

        const strongWarning = reasons.length >= 2;
        showEvidenceWarning(
            strongWarning ? 'red' : 'amber',
            strongWarning ? 'Replace this photo' : 'Check this photo',
            strongWarning ? 'This image may be rejected as invalid evidence.' : 'This image may not be a strong emergency-scene photo.',
            strongWarning
                ? 'Please replace it with a clearer real accident or emergency scene photo before sending.'
                : 'You can continue, but it is safer to replace this with a clearer camera photo of the actual scene.',
            reasons,
        );
    };

    evidenceReplaceTrigger?.addEventListener('click', () => {
        if (photoCaptureInput instanceof HTMLInputElement) {
            photoCaptureInput.click();
            return;
        }

        evidenceInput?.click();
    });

    warningDismissTrigger?.addEventListener('click', () => {
        hideEvidenceWarning();
    });

    const updateCivilianReadiness = () => {
        if (!isCivilian) {
            return true;
        }

        const photoReady = hasRequiredPhoto();
        const gpsReady = hasRequiredGps();
        const descriptionReady = hasRequiredDescription();
        const selfieReady = hasRequiredSelfie();
        const readyForSelfieGate = photoReady && gpsReady && descriptionReady;
        const allReady = readyForSelfieGate && selfieReady;

        setBadgeState(photoRequirement, photoReady ? 'green' : 'red', photoReady ? 'Ready' : 'Still required');
        setBadgeState(selfieRequirement, selfieReady ? 'green' : 'blue', selfieReady ? 'Ready' : 'Opens on send');
        setBadgeState(gpsRequirement, gpsReady ? 'green' : 'red', gpsReady ? 'Ready' : 'Still required');
        setBadgeState(descriptionRequirement, descriptionReady ? 'green' : 'red', descriptionReady ? 'Ready' : 'Still required');

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = !readyForSelfieGate;
            submitButton.setAttribute('aria-disabled', readyForSelfieGate ? 'false' : 'true');
        }

        if (submitStatus) {
            submitStatus.textContent = allReady
                ? 'Verification selfie is ready. Sending can continue.'
                : readyForSelfieGate
                    ? 'Tap Send Report to open selfie verification before final submit.'
                    : 'Complete the real scene photo, GPS lock, and short description first. Screenshots, app UI, and unrelated dummy photos are not accepted.';
        }

        return readyForSelfieGate;
    };

    const setSelfieCameraStatus = (message) => {
        if (selfieCameraStatus) {
            selfieCameraStatus.textContent = message;
        }
    };

    const showSelfieCameraModal = () => {
        if (selfieCameraModal instanceof HTMLElement) {
            selfieCameraModal.hidden = false;
        }
    };

    const hideSelfieCameraModal = () => {
        if (selfieCameraModal instanceof HTMLElement) {
            selfieCameraModal.hidden = true;
        }
    };

    const stopSelfieCamera = () => {
        if (selfieCameraStream) {
            selfieCameraStream.getTracks().forEach((track) => track.stop());
            selfieCameraStream = null;
        }

        if (selfieCameraPreview instanceof HTMLVideoElement) {
            selfieCameraPreview.pause();
            selfieCameraPreview.srcObject = null;
        }
    };

    const canUseDirectCamera = () => {
        const host = window.location.hostname;
        const isLocalhost = ['localhost', '127.0.0.1'].includes(host);

        return Boolean(navigator.mediaDevices?.getUserMedia) && (window.isSecureContext || isLocalhost);
    };

    const requestFrontCameraStream = async () => {
        const preferredConstraints = {
            audio: false,
            video: {
                facingMode: { exact: 'user' },
                width: { ideal: 720 },
                height: { ideal: 960 },
            },
        };

        try {
            return await navigator.mediaDevices.getUserMedia(preferredConstraints);
        } catch {
            return navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'user' },
                    width: { ideal: 720 },
                    height: { ideal: 960 },
                },
            });
        }
    };

    const assignSelfieFile = (file) => {
        if (!(selfieCaptureInput instanceof HTMLInputElement) || !(file instanceof File) || typeof DataTransfer === 'undefined') {
            return false;
        }

        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            selfieCaptureInput.files = transfer.files;
            clearInputFile(selfieInput);
            return true;
        } catch {
            return false;
        }
    };

    const canvasToJpegBlob = (canvas) => new Promise((resolve) => {
        if (typeof canvas.toBlob === 'function') {
            canvas.toBlob(resolve, 'image/jpeg', 0.88);
            return;
        }

        try {
            fetch(canvas.toDataURL('image/jpeg', 0.88))
                .then((response) => response.blob())
                .then(resolve)
                .catch(() => resolve(null));
        } catch {
            resolve(null);
        }
    });

    const openPhoneSelfieFallback = () => {
        stopSelfieCamera();
        hideSelfieCameraModal();

        if (submitStatus) {
            submitStatus.textContent = 'Opening phone camera for selfie verification.';
        }

        if (selfieCaptureInput instanceof HTMLInputElement) {
            selfieCaptureInput.click();
            return;
        }

        selfieInput?.click();
    };

    const startFrontSelfieCamera = async () => {
        if (!(selfieCameraPreview instanceof HTMLVideoElement) || !(selfieCameraModal instanceof HTMLElement)) {
            return false;
        }

        if (!canUseDirectCamera()) {
            setSelfieCameraStatus('This browser requires HTTPS for direct front-camera preview. Opening the phone camera instead.');
            return false;
        }

        showSelfieCameraModal();
        stopSelfieCamera();
        setSelfieCameraStatus('Requesting the front camera. Please allow camera access if the browser asks.');

        if (selfieCameraCaptureButton instanceof HTMLButtonElement) {
            selfieCameraCaptureButton.disabled = true;
        }

        if (selfieCameraRetryButton instanceof HTMLButtonElement) {
            selfieCameraRetryButton.hidden = true;
        }

        try {
            selfieCameraStream = await requestFrontCameraStream();
            selfieCameraPreview.srcObject = selfieCameraStream;

            const playAttempt = selfieCameraPreview.play();
            if (playAttempt instanceof Promise) {
                await playAttempt;
            }

            setSelfieCameraStatus('Front camera is ready. Center your face, then capture the verification selfie.');

            if (selfieCameraCaptureButton instanceof HTMLButtonElement) {
                selfieCameraCaptureButton.disabled = false;
            }

            return true;
        } catch {
            stopSelfieCamera();
            setSelfieCameraStatus('The browser could not open the front camera directly. You can retry or use the phone camera fallback.');

            if (selfieCameraRetryButton instanceof HTMLButtonElement) {
                selfieCameraRetryButton.hidden = false;
            }

            if (selfieCameraCaptureButton instanceof HTMLButtonElement) {
                selfieCameraCaptureButton.disabled = true;
            }

            return true;
        }
    };

    const captureFrontSelfie = async () => {
        if (!(selfieCameraPreview instanceof HTMLVideoElement) || !(selfieCameraCanvas instanceof HTMLCanvasElement)) {
            openPhoneSelfieFallback();
            return;
        }

        const width = selfieCameraPreview.videoWidth;
        const height = selfieCameraPreview.videoHeight;

        if (!width || !height) {
            setSelfieCameraStatus('Front camera preview is not ready yet. Please wait a moment, then try again.');
            return;
        }

        selfieCameraCanvas.width = width;
        selfieCameraCanvas.height = height;

        const context = selfieCameraCanvas.getContext('2d');
        if (!context) {
            openPhoneSelfieFallback();
            return;
        }

        context.save();
        context.translate(width, 0);
        context.scale(-1, 1);
        context.drawImage(selfieCameraPreview, 0, 0, width, height);
        context.restore();

        const blob = await canvasToJpegBlob(selfieCameraCanvas);

        if (!(blob instanceof Blob)) {
            openPhoneSelfieFallback();
            return;
        }

        let selfieFile = null;
        try {
            selfieFile = new File([blob], `verification-selfie-${Date.now()}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
        } catch {
            openPhoneSelfieFallback();
            return;
        }

        if (!assignSelfieFile(selfieFile)) {
            openPhoneSelfieFallback();
            return;
        }

        updateSelfiePreview(selfieFile);
        stopSelfieCamera();
        hideSelfieCameraModal();
        submitAfterSelfieVerification();
    };

    const openSelfieVerification = async () => {
        pendingSubmitAfterSelfie = true;

        if (submitStatus) {
            submitStatus.textContent = 'Opening front-camera selfie verification. Capture your face clearly to submit the report.';
        }

        if (await startFrontSelfieCamera()) {
            return;
        }

        openPhoneSelfieFallback();
    };

    const submitAfterSelfieVerification = () => {
        pendingSubmitAfterSelfie = false;
        if (submitStatus) {
            submitStatus.textContent = 'Selfie verified. Sending report now...';
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitButton instanceof HTMLButtonElement ? submitButton : undefined);
            return;
        }

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.click();
            return;
        }

        form.submit();
    };

    const updateEvidencePreview = (file) => {
        revokePreviewUrl(evidencePreviewUrl);
        evidencePreviewUrl = null;

        if (!(file instanceof File)) {
            if (evidencePreviewImage) {
                evidencePreviewImage.hidden = true;
                evidencePreviewImage.removeAttribute('src');
            }

            if (evidencePreviewVideo) {
                evidencePreviewVideo.pause();
                evidencePreviewVideo.hidden = true;
                evidencePreviewVideo.removeAttribute('src');
                evidencePreviewVideo.load();
            }

            if (evidencePreviewName) {
                evidencePreviewName.textContent = 'No photo or video selected yet.';
            }

            setBadgeState(evidenceBadge, 'neutral', 'Evidence pending');
            hideEvidenceWarning();
            updateCivilianReadiness();
            return;
        }

        evidencePreviewUrl = createPreviewUrl(file);
        const isVideo = file.type.startsWith('video/');

        if (evidencePreviewName) {
            evidencePreviewName.textContent = file.name;
        }

        if (isVideo) {
            if (evidencePreviewImage) {
                evidencePreviewImage.hidden = true;
                evidencePreviewImage.removeAttribute('src');
            }

            if (evidencePreviewVideo && evidencePreviewUrl) {
                evidencePreviewVideo.src = evidencePreviewUrl;
                evidencePreviewVideo.hidden = false;
                evidencePreviewVideo.load();
            }

            setBadgeState(evidenceBadge, 'blue', 'Video ready');
            void inspectEvidenceFile(file);
            updateCivilianReadiness();
            return;
        }

        if (evidencePreviewVideo) {
            evidencePreviewVideo.pause();
            evidencePreviewVideo.hidden = true;
            evidencePreviewVideo.removeAttribute('src');
            evidencePreviewVideo.load();
        }

        if (evidencePreviewImage && evidencePreviewUrl) {
            evidencePreviewImage.src = evidencePreviewUrl;
            evidencePreviewImage.hidden = false;
        }

        setBadgeState(evidenceBadge, 'green', 'Photo ready');
        void inspectEvidenceFile(file);
        updateCivilianReadiness();
    };

    const updateVideoBadge = () => {
        const videoFile = getActiveVideoFile();
        setBadgeState(videoBadge, videoFile instanceof File ? 'blue' : 'neutral', videoFile instanceof File ? 'Video ready' : 'Optional');
    };

    const updateSelfiePreview = (file) => {
        revokePreviewUrl(selfiePreviewUrl);
        selfiePreviewUrl = null;

        if (!(file instanceof File)) {
            if (selfiePreviewImage) {
                selfiePreviewImage.hidden = true;
                selfiePreviewImage.removeAttribute('src');
            }

            if (selfiePreviewName) {
                selfiePreviewName.textContent = 'No verification selfie selected yet.';
            }

            setBadgeState(selfieBadge, 'neutral', 'Selfie pending');
            updateCivilianReadiness();
            return;
        }

        selfiePreviewUrl = createPreviewUrl(file);

        if (selfiePreviewImage && selfiePreviewUrl) {
            selfiePreviewImage.src = selfiePreviewUrl;
            selfiePreviewImage.hidden = false;
        }

        if (selfiePreviewName) {
            selfiePreviewName.textContent = file.name;
        }

        setBadgeState(selfieBadge, 'green', 'Selfie ready');
        updateCivilianReadiness();
    };

    geoButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setGeoStatus('Browser geolocation is not available on this device.');
            setBadgeState(gpsBadge, 'red', 'GPS blocked');
            return;
        }

        const host = window.location.hostname;
        const isLocalhost = ['localhost', '127.0.0.1'].includes(host);
        if (!window.isSecureContext && !isLocalhost) {
            setGeoStatus('Browser GPS is blocked on this non-secure page. Use localhost or HTTPS to allow location access.');
            setBadgeState(gpsBadge, 'red', 'GPS blocked');
            return;
        }

        if (geoButton instanceof HTMLButtonElement) {
            geoButton.disabled = true;
        }

        setGeoStatus('Getting your current GPS coordinates...');
        setBadgeState(gpsBadge, 'amber', 'Locking GPS');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const latitude = position.coords.latitude.toFixed(6);
                const longitude = position.coords.longitude.toFixed(6);

                if (latitudeInput instanceof HTMLInputElement) {
                    latitudeInput.value = latitude;
                    latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    latitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (longitudeInput instanceof HTMLInputElement) {
                    longitudeInput.value = longitude;
                    longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    longitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (locationInput instanceof HTMLInputElement && !locationInput.value.trim()) {
                    locationInput.value = `GPS ${latitude}, ${longitude}`;
                    locationInput.dispatchEvent(new Event('input', { bubbles: true }));
                    locationInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                setGeoStatus(`GPS coordinates loaded: ${latitude}, ${longitude}`);
                setBadgeState(gpsBadge, 'green', 'GPS ready');
                updateCivilianReadiness();

                if (geoButton instanceof HTMLButtonElement) {
                    geoButton.disabled = false;
                }
            },
            (error) => {
                const messageMap = {
                    1: 'Location permission was denied. Allow location access and try again.',
                    2: 'Your location is unavailable right now. Try again in an open area or with GPS enabled.',
                    3: 'Location request timed out. Please try again.',
                };

                setGeoStatus(messageMap[error.code] ?? 'Unable to get GPS coordinates from the browser.');
                setBadgeState(gpsBadge, error.code === 1 ? 'red' : 'amber', error.code === 1 ? 'GPS denied' : 'GPS retry');
                updateCivilianReadiness();

                if (geoButton instanceof HTMLButtonElement) {
                    geoButton.disabled = false;
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            }
        );
    });

    triggerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const trigger = button.getAttribute('data-capture-trigger');

            if (trigger === 'photo') {
                photoCaptureInput?.click();
            } else if (trigger === 'video') {
                videoCaptureInput?.click();
            } else if (trigger === 'selfie') {
                void openSelfieVerification();
            } else if (trigger === 'gps') {
                gpsFocusButton?.blur();
                geoButton?.click();
            }
        });
    });

    selfieCameraCaptureButton?.addEventListener('click', () => {
        void captureFrontSelfie();
    });

    selfieCameraRetryButton?.addEventListener('click', () => {
        void startFrontSelfieCamera();
    });

    selfieCameraFallbackButton?.addEventListener('click', () => {
        openPhoneSelfieFallback();
    });

    selfieCameraCancelButton?.addEventListener('click', () => {
        pendingSubmitAfterSelfie = false;
        stopSelfieCamera();
        hideSelfieCameraModal();

        if (submitStatus) {
            submitStatus.textContent = 'Selfie verification was cancelled. Tap Send Report when you are ready to verify.';
        }
    });

    photoCaptureInput?.addEventListener('change', () => {
        clearInputFile(evidenceInput);
        updateEvidencePreview(getActiveEvidenceFile());
    });

    videoCaptureInput?.addEventListener('change', () => {
        updateVideoBadge();
        updateEvidencePreview(getActiveEvidenceFile());
    });

    selfieCaptureInput?.addEventListener('change', () => {
        clearInputFile(selfieInput);
        updateSelfiePreview(getActiveSelfieFile());

        if (pendingSubmitAfterSelfie && hasRequiredSelfie()) {
            submitAfterSelfieVerification();
        }
    });

    evidenceInput?.addEventListener('change', () => {
        clearInputFile(photoCaptureInput);
        clearInputFile(videoCaptureInput);
        updateVideoBadge();
        updateEvidencePreview(getActiveEvidenceFile());
    });

    selfieInput?.addEventListener('change', () => {
        clearInputFile(selfieCaptureInput);
        updateSelfiePreview(getActiveSelfieFile());

        if (pendingSubmitAfterSelfie && hasRequiredSelfie()) {
            submitAfterSelfieVerification();
        }
    });

    updateEvidencePreview(getActiveEvidenceFile());
    updateVideoBadge();
    updateSelfiePreview(getActiveSelfieFile());

    if ((latitudeInput instanceof HTMLInputElement && latitudeInput.value.trim()) || (longitudeInput instanceof HTMLInputElement && longitudeInput.value.trim())) {
        setBadgeState(gpsBadge, 'green', 'GPS ready');
    }

    [latitudeInput, longitudeInput, descriptionInput].forEach((element) => {
        element?.addEventListener('input', () => {
            if (hasRequiredGps()) {
                setBadgeState(gpsBadge, 'green', 'GPS ready');
            } else {
                setBadgeState(gpsBadge, 'neutral', 'GPS pending');
            }

            updateCivilianReadiness();
        });
    });

    form.addEventListener('submit', (event) => {
        if (!isCivilian) {
            return;
        }

        if (!updateCivilianReadiness()) {
            event.preventDefault();
            setGeoStatus('Complete the scene photo, GPS lock, and short description before sending.');
            return;
        }

        if (!hasRequiredSelfie()) {
            event.preventDefault();
            void openSelfieVerification();
        }
    });

    updateCivilianReadiness();

    window.addEventListener('beforeunload', () => {
        stopSelfieCamera();
        revokePreviewUrl(evidencePreviewUrl);
        revokePreviewUrl(selfiePreviewUrl);
    });
});
