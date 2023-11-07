let keyValueMap = {}

export function iterateObject(obj, currentKey = '') {
  for (const key in obj) {
    if (typeof obj[key] === 'object') {
      iterateObject(obj[key], currentKey + key + '.')
    } else if (Array.isArray(obj[key])) {
      obj[key].forEach((item, index) => {
        keyValueMap[`${currentKey}${key}[${index}]`] = item
      })
    } else {
      keyValueMap[`${currentKey}${key}`] = obj[key]
    }
  }

  return keyValueMap
}
