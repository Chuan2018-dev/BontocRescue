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
    const selfiePreviewImage = root.querySelector('[data-selfie-preview-image]');
    const selfiePreviewName = root.querySelector('[data-selfie-preview-name]');
    const evidenceBadge = root.querySelector('[data-capture-badge="evidence"]');
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

    const updateCivilianReadiness = () => {
        if (!isCivilian) {
            return true;
        }

        const photoReady = hasRequiredPhoto();
        const selfieReady = hasRequiredSelfie();
        const gpsReady = hasRequiredGps();
        const descriptionReady = hasRequiredDescription();
        const allReady = photoReady && selfieReady && gpsReady && descriptionReady;

        setBadgeState(photoRequirement, photoReady ? 'green' : 'red', photoReady ? 'Ready' : 'Still required');
        setBadgeState(selfieRequirement, selfieReady ? 'green' : 'red', selfieReady ? 'Ready' : 'Still required');
        setBadgeState(gpsRequirement, gpsReady ? 'green' : 'red', gpsReady ? 'Ready' : 'Still required');
        setBadgeState(descriptionRequirement, descriptionReady ? 'green' : 'red', descriptionReady ? 'Ready' : 'Still required');

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = !allReady;
            submitButton.setAttribute('aria-disabled', allReady ? 'false' : 'true');
        }

        if (submitStatus) {
            submitStatus.textContent = allReady
                ? 'All required steps are ready. You can send the emergency report now.'
                : 'Complete the scene photo, verification selfie, GPS lock, and short description first. The Send Emergency Report button stays locked until all four are ready.';
        }

        return allReady;
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
        updateCivilianReadiness();
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
                selfieCaptureInput?.click();
            } else if (trigger === 'gps') {
                gpsFocusButton?.blur();
                geoButton?.click();
            }
        });
    });

    photoCaptureInput?.addEventListener('change', () => {
        clearInputFile(videoCaptureInput);
        clearInputFile(evidenceInput);
        updateEvidencePreview(getActiveEvidenceFile());
    });

    videoCaptureInput?.addEventListener('change', () => {
        clearInputFile(photoCaptureInput);
        clearInputFile(evidenceInput);
        updateEvidencePreview(getActiveEvidenceFile());
    });

    selfieCaptureInput?.addEventListener('change', () => {
        clearInputFile(selfieInput);
        updateSelfiePreview(getActiveSelfieFile());
    });

    evidenceInput?.addEventListener('change', () => {
        clearInputFile(photoCaptureInput);
        clearInputFile(videoCaptureInput);
        updateEvidencePreview(getActiveEvidenceFile());
    });

    selfieInput?.addEventListener('change', () => {
        clearInputFile(selfieCaptureInput);
        updateSelfiePreview(getActiveSelfieFile());
    });

    updateEvidencePreview(getActiveEvidenceFile());
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
        if (isCivilian && !updateCivilianReadiness()) {
            event.preventDefault();
            setGeoStatus('Complete the required capture steps before sending the emergency report.');
        }
    });

    updateCivilianReadiness();

    window.addEventListener('beforeunload', () => {
        revokePreviewUrl(evidencePreviewUrl);
        revokePreviewUrl(selfiePreviewUrl);
    });
});
