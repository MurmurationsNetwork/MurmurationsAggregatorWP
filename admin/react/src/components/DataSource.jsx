import { schemas } from '../data/schemas'
import { useEffect, useState } from 'react'
import { getCountries } from '../utils/getCountries'
import PropTypes from 'prop-types'

export default function DataSource({
  formData,
  handleInputChange,
  selectedCountry,
  setSelectedCountry
}) {
  const [countries, setCountries] = useState([])

  useEffect(() => {
    getCountries().then(countries => {
      const countryKeys = Object.keys(countries)
      setCountries(countryKeys)
    })
  }, [])

  const handleCountryChange = event => {
    const selected = Array.from(event.target.options)
      .filter(option => option.selected)
      .map(option => option.value)

    setSelectedCountry(selected)
  }

  return (
    <div>
      <h2 className="text-xl mt-4">Data Source</h2>
      <div className="border-2 border-dotted border-red-500 p-4 mt-2">
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
          <label className="block text-gray-700 font-bold mb-2" htmlFor="name">
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
          <label className="block text-gray-700 font-bold mb-2" htmlFor="lat">
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
          <label className="block text-gray-700 font-bold mb-2" htmlFor="lon">
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
          <label className="block text-gray-700 font-bold mb-2" htmlFor="range">
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
          <label className="block text-gray-700 font-bold mb-2" htmlFor="tags">
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
      </div>
    </div>
  )
}

DataSource.propTypes = {
  formData: PropTypes.shape({
    data_url: PropTypes.string,
    schema: PropTypes.string,
    name: PropTypes.string,
    lat: PropTypes.string,
    lon: PropTypes.string,
    range: PropTypes.string,
    locality: PropTypes.string,
    region: PropTypes.string,
    tags: PropTypes.string,
    tags_filter: PropTypes.string,
    tags_exact: PropTypes.bool,
    primary_url: PropTypes.string
  }),
  handleInputChange: PropTypes.func.isRequired,
  selectedCountry: PropTypes.array.isRequired,
  setSelectedCountry: PropTypes.func.isRequired
}
