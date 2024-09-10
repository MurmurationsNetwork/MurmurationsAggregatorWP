export const getSchemas = async () => {
  try {
    const response = await fetch(
      'https://test-library.murmurations.network/v2/schemas'
    )
    const data = await response.json()

    // Remove test-schema from the list
    return data?.data
      ?.map(schema => ({
        title: schema.title,
        name: schema.name
      }))
      .filter(schema => !schema.name.includes('test_schema'))
  } catch (error) {
    alert(
      `Error getting schemas, please contact the administrator, error: ${error}`
    )
  }
}
