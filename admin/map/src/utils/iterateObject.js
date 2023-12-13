let keyValueMap = {}

export function iterateObject(obj, currentKey = '') {
  if (!currentKey) {
    keyValueMap = {}
  }
  for (const key in obj) {
    if (typeof obj[key] === 'object') {
      if (Array.isArray(obj[key])) {
        obj[key].forEach((item, index) => {
          if (typeof item === 'object') {
            iterateObject(item, `${currentKey}${key}[${index}].`)
          } else {
            keyValueMap[`${currentKey}${key}[${index}]`] = item
          }
        })
      } else {
        iterateObject(obj[key], `${currentKey}${key}.`)
      }
    } else {
      keyValueMap[`${currentKey}${key}`] = obj[key]
    }
  }

  return keyValueMap
}
