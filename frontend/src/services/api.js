const API_BASE = 'http://localhost:8001/api';

export const fetchComments = async () => {
    const response = await fetch(`${API_BASE}/comments`);
    if (!response.ok) {
        throw new Error('Failed to fetch comments');
    }
    return response.json();
};

export const analyzeComments = async (id = null) => {
    const options = {
        method: 'POST',
    };

    if (id) {
        options.headers = { 'Content-Type': 'application/json' };
        options.body = JSON.stringify({ id });
    }

    const response = await fetch(`${API_BASE}/analyze`, options);
    if (!response.ok) {
        throw new Error('Failed to analyze comments');
    }
    return response.json();
};

export const resetAnalysis = async () => {
    const response = await fetch(`${API_BASE}/reset`, {
        method: 'POST',
    });
    if (!response.ok) {
        throw new Error('Failed to reset analysis');
    }
    return response.json();
};

export const generateResponse = async (id, type, language = 'English') => {
    const response = await fetch(`${API_BASE}/generate-response`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, type, language }),
    });
    if (!response.ok) {
        throw new Error('Failed to generate response');
    }
    return response.json();
};

export const submitAction = async (id, status, responseText) => {
    const response = await fetch(`${API_BASE}/submit-action`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status, response: responseText }),
    });
    if (!response.ok) {
        throw new Error('Failed to submit action');
    }
    return response.json();
};

export const translateComment = async (id) => {
    const response = await fetch(`${API_BASE}/translate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    });
    if (!response.ok) {
        throw new Error('Failed to translate comment');
    }
    return response.json();
};
