export const PASSWORD_MIN_LENGTH = 8;

export function getPasswordChecks(password) {
  const value = String(password || "");
  return {
    minLength: value.length >= PASSWORD_MIN_LENGTH,
    hasLetter: /[a-zA-Z]/.test(value),
    hasNumber: /\d/.test(value),
  };
}

export function getPasswordIssues(password) {
  const checks = getPasswordChecks(password);
  const issues = [];

  if (!checks.minLength) {
    issues.push("En az 8 karakter olmalıdır.");
  }
  if (!checks.hasLetter) {
    issues.push("En az bir harf içermelidir.");
  }
  if (!checks.hasNumber) {
    issues.push("En az bir rakam içermelidir.");
  }

  return issues;
}

export function isPasswordValid(password) {
  const checks = getPasswordChecks(password);
  return checks.minLength && checks.hasLetter && checks.hasNumber;
}

export function getConfirmPasswordError(password, confirmPassword) {
  if (!confirmPassword) {
    return "";
  }

  return password === confirmPassword ? "" : "Şifreler uyuşmuyor.";
}
