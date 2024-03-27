export function schemaContent(responseData, linkType) {
  let content = ''
  const schema = responseData?.profile_data?.linked_schemas[0]
  const imageUrl = responseData?.profile_data?.image
  switch (schema) {
    case 'organizations_schema-v1.0.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(content, responseData?.profile_data?.name)
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.primary_url,
        responseData.post_url,
        linkType
      )
      break
    case 'people_schema-v0.1.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(content, responseData?.profile_data?.name)
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.primary_url,
        responseData.post_url,
        linkType
      )
      break
    case 'offers_wants_schema-v0.1.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(content, responseData?.profile_data?.title)
      content = addExchangeTypeToContent(
        content,
        responseData?.profile_data?.exchange_type
          ? responseData?.profile_data?.exchange_type === 'offer'
            ? 'Offer'
            : 'Want'
          : ''
      )
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addContactToContent(
        content,
        responseData?.profile_data?.contact_details
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.details_url,
        responseData.post_url,
        linkType
      )
      break
  }

  return content
}

function addImageToContent(content, imageUrl) {
  if (imageUrl) {
    content += `<img src='${imageUrl}' style='max-height: 50px; width: auto; display: inherit' id='profile_image' alt="" />`

    const img = new Image()
    img.src = imageUrl
    img.onerror = () => {
      const profileImage = document.getElementById('profile_image')
      if (profileImage) {
        profileImage.style.display = 'none'
      }
    }
  }
  return content
}

function addTitleToContent(content, title) {
  if (title) {
    content += `<p><strong>${title}</strong></p>`
  }
  return content
}

function addExchangeTypeToContent(content, exchange_type) {
  if (exchange_type) {
    content += `<p><em>${exchange_type}</em></p>`
  }
  return content
}

function addDescriptionToContent(content, description) {
  if (description) {
    content += `<p>${limitString(description, 200)}</p>`
  }
  return content
}

function addContactToContent(content, contactDetails) {
  if (contactDetails?.email) {
    content += `<a href="mailto:${contactDetails.email}">${contactDetails.email}</a>`
  }
  if (contactDetails?.email && contactDetails?.contact_form) {
    content += ' - '
  }
  if (contactDetails?.contact_form) {
    content += `<a href="${contactDetails.contact_form}" target="_blank">${contactDetails.contact_form}</a>`
  }
  return content
}

function addUrlToContent(content, primaryUrl, postUrl, linkType) {
  if (primaryUrl && linkType === 'primary') {
    content += `<p><a target='_blank' rel='noreferrer' href='${primaryUrl}'>${primaryUrl}</a></p>`
  }
  if (postUrl && linkType === 'wp') {
    content += `<p><a href='${postUrl}'>More...</a></p>`
  }
  return content
}

function limitString(inputString, maxLength) {
  if (inputString.length <= maxLength) {
    return inputString
  } else {
    return inputString.substring(0, maxLength) + '...'
  }
}
