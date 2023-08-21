import { useEffect, useState } from 'react'
import Table from './components/Table'

const schemas = [
  { title: 'An Organization', name: 'organizations_schema-v1.0.0' },
  { title: 'A Person', name: 'people_schema-v0.1.0' },
  { title: 'An Offer or Want', name: 'offers_wants_schema-v0.1.0' }
]

export default function App() {
  const [formData, setFormData] = useState({
    data_url: '',
    schema: 'organizations_schema-v1.0.0',
    name: '',
    lat: '',
    lon: '',
    range: '',
    locality: '',
    region: '',
    tags: '',
    tags_filter: 'or',
    tags_exact: false,
    primary_url: ''
  })
  const [countries, setCountries] = useState([])
  const [selectedCountry, setSelectedCountry] = useState([])
  const [profileList, setProfileList] = useState([])
  const [selectedIds, setSelectedIds] = useState([])

  useEffect(() => {
    getCountries().then(countries => {
      const countryKeys = Object.keys(countries)
      setCountries(countryKeys)
    })
  }, [])

  const getCountries = async () => {
    try {
      const res = await fetch(
        'https://test-library.murmurations.network/v2/countries'
      )
      return await res.json()
    } catch (error) {
      alert(
        `Error getting countries, please contact the administrator, error: ${error}`
      )
    }
  }

  // Table
  const toggleSelectAll = () => {
    if (selectedIds.length === profileList.length) {
      setSelectedIds([])
    } else {
      setSelectedIds(profileList.map(response => response.id))
    }
  }

  const toggleSelect = id => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter(selectedId => selectedId !== id))
    } else {
      setSelectedIds([...selectedIds, id])
    }
  }

  const handleInputChange = event => {
    const { name, value, type, checked } = event.target
    const newValue = type === 'checkbox' ? checked : value

    setFormData(prevData => {
      const newData = Object.assign({}, prevData)
      newData[name] = newValue
      return newData
    })
  }

  const handleCountryChange = event => {
    const selected = Array.from(event.target.options)
      .filter(option => option.selected)
      .map(option => option.value)

    setSelectedCountry(selected)
  }

  const handleSubmit = async event => {
    event.preventDefault()

    const queryParams = []
    for (const key in formData) {
      if (formData[key] !== '' && key !== 'data_url') {
        queryParams.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(formData[key])}`
        )
      }
    }
    const queryString = queryParams.join('&')
    const pageQueries = 'page=1&page_size=500'

    const urlWithParams =
      formData.data_url +
      '?' +
      (queryString ? `${queryString}&` : '') +
      pageQueries

    console.log(urlWithParams)

    try {
      const response = await fetch(urlWithParams)
      if (response.ok) {
        const responseData = await response.json()
        if (responseData.data && responseData.data.length === 0) {
          alert('Error: No data found. Please try again.')
          return
        }
        if (
          responseData.meta &&
          responseData.meta.total_pages &&
          responseData.meta.total_pages > 2
        ) {
          alert('Error: Too many pages of data. Please narrow your search.')
          return
        }
        const dataWithIds = responseData.data.map((item, index) => {
          item.id = index + 1
          return item
        })
        setProfileList(dataWithIds)
      } else {
        alert(`Error: ${response.status} ${response}`)
      }
    } catch (error) {
      alert(`Handle Submit error: ${error}`)
    }
  }

  const handleProfilesSubmit = async event => {
    event.preventDefault()

    const selectedProfiles = profileList.filter(profile =>
      selectedIds.includes(profile.id)
    )

    // todo: send selectedProfiles to WordPress server
    console.log(selectedProfiles)
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Aggregator</h1>

      <div className="flex">
        <div className="w-1/2 mt-4">
          {profileList.length === 0 ? (
            <div>
              <h2 className="text-xl">Create Data Source</h2>
              <form onSubmit={handleSubmit} className="p-6">
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="data_url"
                  >
                    Data URL
                  </label>
                  <input
                    type="text"
                    id="data_url"
                    name="data_url"
                    value={formData.data_url}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                    required={true}
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="schema"
                  >
                    Schema
                  </label>
                  <select
                    id="schema"
                    name="schema"
                    value={formData.schema}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    {schemas.map(schema => (
                      <option key={schema.name} value={schema.name}>
                        {schema.title}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="name"
                  >
                    Name
                  </label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="lat"
                  >
                    Latitude
                  </label>
                  <input
                    type="number"
                    id="lat"
                    name="lat"
                    min="-90"
                    max="90"
                    step="any"
                    value={formData.lat}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="lon"
                  >
                    Longitude
                  </label>
                  <input
                    type="number"
                    id="lon"
                    name="lon"
                    min="-180"
                    max="180"
                    step="any"
                    value={formData.lon}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="range"
                  >
                    Range (i.e. 25km, 15mi)
                  </label>
                  <input
                    type="text"
                    id="range"
                    name="range"
                    value={formData.range}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="locality"
                  >
                    Locality
                  </label>
                  <input
                    type="text"
                    id="locality"
                    name="locality"
                    value={formData.locality}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="region"
                  >
                    Region
                  </label>
                  <input
                    type="text"
                    id="region"
                    name="region"
                    value={formData.region}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="country"
                  >
                    Country
                  </label>
                  <select
                    multiple={true}
                    id="country"
                    name="country"
                    value={selectedCountry}
                    onChange={handleCountryChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    {countries.map(country => (
                      <option
                        key={country}
                        value={country}
                        selected={selectedCountry.includes(country)}
                      >
                        {country}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags"
                  >
                    Tags
                  </label>
                  <input
                    type="text"
                    id="tags"
                    name="tags"
                    value={formData.tags}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags_filter"
                  >
                    Tags Filter
                  </label>
                  <select
                    id="tags_filter"
                    name="tags_filter"
                    value={formData.tags_filter}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    <option value="or">OR</option>
                    <option value="and">AND</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags_exact"
                  >
                    Tags Exact
                  </label>
                  <input
                    type="checkbox"
                    id="tags_exact"
                    name="tags_exact"
                    checked={formData.tags_exact}
                    onChange={handleInputChange}
                    className="mr-2"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="primary_url"
                  >
                    Primary URL
                  </label>
                  <input
                    type="text"
                    id="primary_url"
                    name="primary_url"
                    value={formData.primary_url}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mt-6">
                  <button
                    type="submit"
                    className="rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                  >
                    Submit
                  </button>
                </div>
              </form>
            </div>
          ) : (
            <div>
              <h2 className="text-xl mt-4">Data Select</h2>
              <Table
                tableList={profileList}
                selectedIds={selectedIds}
                onSelectAll={toggleSelectAll}
                onSelect={toggleSelect}
              />
              <form onSubmit={handleProfilesSubmit} className="p-6">
                <div className="mt-6">
                  <button
                    type="submit"
                    className="rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                  >
                    Submit
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>
        <div className="w-1/2 mt-4">
          <h2 className="text-xl">Map Data</h2>
        </div>
      </div>
    </div>
  )
}
