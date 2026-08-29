export default function guestFormValidation(guestFields, field, message) {
  if (
    guestFields[field] === "" ||
    guestFields[field] === null ||
    guestFields[field] === undefined
  ) {
    return [message];
  }
}
