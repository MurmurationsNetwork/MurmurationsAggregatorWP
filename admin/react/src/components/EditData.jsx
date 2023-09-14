import MapSettings from './MapSettings'
import { updateWpMap } from '../utils/api'
import { formDefaults } from '../data/formDefaults'
import PropTypes from 'prop-types'

export default function EditData({
  formData,
  handleInputChange,
  setIsLoading,
  setIsEdit,
  setFormData,
  setTagSlug,
  tagSlug,
  getMaps,
  isLoading
}) {
  const handleEditSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      const response = await updateWpMap(
        tagSlug,
        formData.map_name,
        formData.map_center_lat,
        formData.map_center_lon,
        formData.map_scale
      )

      if (!response.ok) {
        const responseData = await response.json()
        alert(`Map Error: ${response.status} ${JSON.stringify(responseData)}`)
      }
    } catch (error) {
      alert(`Edit map error: ${JSON.stringify(error)}`)
    } finally {
      setIsEdit(false)
      setIsLoading(false)
      setFormData(formDefaults)
      setTagSlug(null)
      await getMaps()
    }
  }

  return (
    <div>
      <h2 className="text-xl">Edit Data Source</h2>
      <form onSubmit={handleEditSubmit} className="p-6">
        <MapSettings
          formData={formData}
          handleInputChange={handleInputChange}
        />
        <div className="mt-6">
          <button
            type="submit"
            className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
              isLoading ? 'opacity-50 cursor-not-allowed' : ''
            }`}
          >
            {isLoading ? 'Submitting...' : 'Submit'}
          </button>
        </div>
      </form>
    </div>
  )
}

EditData.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsEdit: PropTypes.func.isRequired,
  setFormData: PropTypes.func.isRequired,
  setTagSlug: PropTypes.func.isRequired,
  tagSlug: PropTypes.string,
  getMaps: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired
}
